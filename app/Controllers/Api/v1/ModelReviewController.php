<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Models\ModelModel;
use App\Models\ModelReviewModel;

class ModelReviewController extends ApiController
{
    protected ModelReviewModel $modelReviewModel;

    public function __construct()
    {
        $this->modelReviewModel  = model(ModelReviewModel::class);
    }

    /**
     * Search for Model reviews by filter.
     *
     * @return mixed
     */
    public function get()
    {
        $inputData = $this->request->getJSON();

        // limit
        $limitStep =  2; // TODO
        $page = $inputData->page ?? 1;
        $limitFrom = (($page) - 1) * $limitStep;

        // sort
        $sort = [];
        if (isset($inputData->sort)) {
            list($sortField, $sortDirection) =  explode( '-', $inputData->sort);
            $sort = [$sortField => $sortDirection];
        }

        // filter & fields
        $filter = [];
        $fields = ['comments'];
        if ($inputData->filter ?? false) {
            if ($inputData->filter->model_id ?? false) {
                $filter['model'] = $inputData->filter->model_id;
            }
        }

        $models = $this->modelReviewModel->query(
            $filter,
            $sort,
            [$limitFrom => $limitStep],
            ['hints' => ['calcRows' => true]],
            $fields
        )->findItems();

        $result = [
            'data' => $models,
            'pager' => [
                'current' => $page,
                'total'   => $this->modelReviewModel->foundRows(), //ceil($this->modelReviewModel->foundRows() / $limitStep),
                'per_page'=> $limitStep,
            ]
        ];
        return $this->respondUpdated($result);
    }

}
