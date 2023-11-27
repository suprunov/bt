<?php

namespace App\Entities;

use App\Entities\Traits\SpecialFields;
use App\Helpers\ArrayHelper;
use App\Helpers\NameHelper;
use App\Helpers\StringHelper;
use App\Helpers\UriHelper;
use CodeIgniter\Entity\Entity;

class Product extends Entity
{
    use SpecialFields;

    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'price'         => 'json',
        'price_default' => 'json',
        'review_totals' => 'json',
        'model'         => 'json',
    ];

    public function __construct(?array $data = null)
    {
        parent::__construct($data);

        helper('common');
    }

    public function getName(string $template = 'default'): object
    {
        $features = $this->getFeatures();
        $type     = $this->getType();
        // Default name
        $name = (object)[
            'full'   => $this->attributes['name'],
            'alt'    => $this->attributes['name'],
            'prefix' => null,
            'brand'  => null,
            'model'  => null,
            'size'   => null,
        ];
        // Generate a name for products that have sizes.
        if (in_array($type->code, ['tyres', 'mototyres', 'disks'])) {
            $sizeStr = NameHelper::product($type->code, $features, $template);
            $typeStr = NameHelper::product($type->code, $features, $template . '_type');
            $altStr  = NameHelper::product($type->code, $features, $template . '_alt');

            $name->alt      = $altStr;
            $name->prefix   = $typeStr;
            $name->brand    = $features['brand']->value ?? null;
            $name->model    = $features['model']->value ?? null;
            $name->size     = $sizeStr;
            $name->full     = "{$name->prefix} {$name->brand} {$name->model} {$name->size}";
        }
        return $name;
    }

    public function getNameByTemplate(string $template = 'default'): string
    {
        return NameHelper::product($this->getType()->code, $this->getFeatures(), $template);
    }

    public function getUri(): string
    {
        return "product/{$this->attributes['code']}-{$this->attributes['id']}/";
    }

    public function getFeatures(): array
    {
        $features = json_decode($this->attributes['features']) ?? [];
        return ArrayHelper::array_make_keys($features, 'code');
    }

    public function getFeaturesGrouped(): array
    {
        $featuresPreview = [];
        $featuresCard    = [];
        $featuresSimilar = [];

        foreach ($this->getFeatures() as $feature) { // TODO studded only for winter season.
            if ($feature->type == 'boolean') {
                $feature->value = $feature->value ? 'Да' : 'Нет'; // TODO
            }
            if ($feature->preview_published) {
                $featuresPreview[$feature->preview_sort] = $feature;
            }
            if ($feature->card_published) { // TODO type sizes
                $featuresCard[$feature->card_sort] = $feature;
            }
            // Features for searching similar products.
            if ($feature->card_similar) {
                $featuresSimilar[$feature->id] = $feature->value_id;
            }
        }
        $featuresCard[0] = (object)['name' => 'Код товара', 'value' => $this->attributes['product_code']];

        ksort($featuresPreview);
        ksort($featuresCard);

        return [
            'preview' => $featuresPreview,
            'card'    => $featuresCard,
            'similar' => $featuresSimilar,
        ];
    }

    public function getType(): object
    {
        return json_decode($this->attributes['type']);
    }

    public function getPictures(): array
    {
        $pictures = json_decode($this->attributes['pictures']) ?? [];
        $picturesFrm = [];
        foreach ($pictures as $picture) {
            $pictureId = $picture->id;
            if (! isset($picturesFrm[$pictureId]))
                $picturesFrm[$pictureId] = (object)['id' => $pictureId, 'sort' => $picture->sort, 'variations' => new \stdClass()];
            unset($picture->id, $picture->sort);
            $picturesFrm[$pictureId]->variations->{$picture->variation_code} = $picture;
        }
        return array_values($picturesFrm);
    }

    public function getPromotions(): array
    {
        return json_decode($this->attributes['promotions']) ?? [];
    }

    public function getLinks(): object
    {
        $features = $this->getFeatures();
        $baseUrl = substr(config('App')->baseURL, 0, -1);
        /*$productUrlOld = match ($this->getType()->code) {
            'tyres' => (isset($features['offroad']['value']) && $features['offroad']['value'] ?
                    '/shiny-4x4/katalog-shin-4x4' : '/catalog-tyres/vendor') .
                "/{$features['brand']->name_eng}/model-{$features['model']->obsolete_model_id}/$this->attributes['obsolete_product_id']/",
            'mototyres' => '/catalog-mototyres/vendor' .
                "/{$features['brand']->name_eng}/model-{$features['model']->obsolete_model_id}/$this->attributes['obsolete_product_id']/",
            'disks' => '/catalog-disks/vendor' .
                "/{$features['brand']->name_eng}/model-{$features['model']->obsolete_model_id}/$this->attributes['obsolete_product_id']/",
            default => '',
        };*/
        $links = [
            'product' => $baseUrl . UriHelper::product($this->attributes['code'], $this->attributes['id']),
            'brand'   => isset($features['brand']->value_code) ?
                $baseUrl . UriHelper::brand($features['brand']->value_code, $features['brand']->value_id) : null,
            'model'   => isset($features['model']->value_code) ?
                $baseUrl . UriHelper::model($features['model']->value_code, $features['model']->value_id) : null,
            //'productOld' => $baseUrl . $productUrlOld,
        ];
        return (object)$links;
    }

    public function getTypeIcon(): object
    {
        $features = $this->getFeatures();
        $type     = $this->getType();

        $icon = (object)[
            'title' => null,
            'class' => null,
        ];
        switch ($type->code) {
            case 'tyres':
            case 'mototyres':
                $featureSeason = $features['season'];
                $featureSpikes = $features['spikes'];
                $icon->title = StringHelper::mb_ucfirst($featureSpikes->value ? $featureSpikes->value_alias : $featureSeason->value_alias);
                $icon->class  = 'i-season ' . $featureSeason->value_code . ($featureSpikes->value ? '-' . $featureSpikes->code : '');
                break;
            case 'disks':
                $featureType = $features['type'];
                $icon->title = StringHelper::mb_ucfirst($featureType->value_alias);
                $icon->class  = 'i-type ' . $featureType->value_code;
                break;
        }
        return $icon;
    }

    public function getQtyToBuy(): int
    {
        return min($this->attributes['qty'], $this->attributes['qty_set']);
    }

    public function getTyreType(): string
    {
        $features = $this->getFeatures();
        $tyreType = $features['season']->value ?? '';
        if ($features['season']->value_code == 'zimnie') {
            $tyreType .= $features['spikes']->value ? ' шипованные' : ' нешипованные';
        }
        return $tyreType;
    }

}
