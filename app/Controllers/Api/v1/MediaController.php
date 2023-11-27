<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Picture;
use App\Entities\PictureVariation;
use App\Models\PictureModel;
use App\Models\PictureTypeModel;
use App\Models\PictureTypeVariationModel;
use App\Models\PictureVariationModel;
use CodeIgniter\Images\Exceptions\ImageException;

class MediaController extends ApiController
{
    protected PictureModel $pictureModel;
    protected PictureTypeModel $pictureTypeModel;
    protected PictureTypeVariationModel $pictureTypeVariationModel;
    protected PictureVariationModel $pictureVariationModel;
    const PICTURE_FOLDER = 'media/pictures/';
    const WATERMARK_PATH = FCPATH . 'media/images/watermark.png';

    public function __construct()
    {
        $this->pictureModel  = model(PictureModel::class);
        $this->pictureTypeModel  = model(PictureTypeModel::class);
        $this->pictureTypeVariationModel  = model(PictureTypeVariationModel::class);
        $this->pictureVariationModel  = model(PictureVariationModel::class);
    }

    public function savePicture(int $pictureTypeId)
    {
        $responseData = (object)[
            'result'  => false,
            'picture' => null,
            'message' => ''
        ];

        $validationRule = [
            'picture' => [
                'label' => 'Picture file',
                'rules' => [
                    'uploaded[picture]',
                    'is_image[picture]',
                    'mime_in[picture,image/jpg,image/jpeg,image/gif,image/png,image/webp,image/svg+xml]',
                    'max_size[picture,2048]',
                    // 'max_dims[picture,4096,3072]', // TODO does not work with svg
                ],
            ],
        ];
        if (! $this->validate($validationRule)) {
            $responseData->message = $this->validator->getErrors();
            return $this->respond($responseData, $this->codes['invalid_data']);
        }

        $pictureType = $this->pictureTypeModel->find($pictureTypeId);
        if ($pictureType === null) {
            $responseData->message = 'Product type not found!';
            return $this->respond($responseData, $this->codes['invalid_data']);
        }

        $img = $this->request->getFile('picture');

        if (! $img->hasMoved()) {

            $fileExtension = $img->getExtension() === 'jpeg' ? 'jpg' : $img->getExtension();
            $fileName      = $this->getRandomName($fileExtension);
            $folderName    = self::PICTURE_FOLDER . $pictureTypeId . '/' . substr($fileName, 11, 2) . '/';
            $filePath      =  FCPATH . $folderName . $fileName;

            // Move the uploaded file to a new location.
            $img->move(FCPATH . $folderName, $fileName);

            // Save picture data to db:
            $this->pictureModel->db->transStart();

            $pictureTypeVariations = $this->pictureTypeVariationModel->query(['type_id' => $pictureTypeId], limit:[])->findItems();
            if (! count($pictureTypeVariations)) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ["Picture type {$pictureTypeId}"]));
            }
            // Save picture
            $picture = new Picture();
            $picture->type_id = $pictureTypeId;
            if (! $this->pictureModel->save($picture)) {
                return $this->failValidationErrors($this->pictureModel->errors());
            }
            $picture->id = $this->pictureModel->getInsertID();
            // Save picture variations
            $variationsResponse = []; //TODO to an object
            foreach ($pictureTypeVariations as $varConfig)
            {
                $pictureVariation = new PictureVariation();
                $pictureVariation->picture_id = $picture->id;
                $pictureVariation->type_variation_id = $varConfig->id;
                $pictureVariation->watermark = $varConfig->watermark;

                if ($varConfig->watermark || $varConfig->extension || $varConfig->height || $varConfig->width)
                {
                    try {
                        $image = \Config\Services::image();
                        $image->withFile($filePath);
                        if ($varConfig->extension && $varConfig->extension !== $fileExtension) {
                            $imgTypeConstant = 'IMAGETYPE_' .
                                strtoupper($varConfig->extension === 'jpg' ? 'jpeg' : $varConfig->extension);
                            $image->convert(constant($imgTypeConstant));
                        }
                        if ($varConfig->height || $varConfig->width) {
                            $image->resize(
                                $varConfig->width,
                                $varConfig->height/*,
                                true,
                                $varConfig->height ? 'height' : 'width'*/
                            );
                        }
                        $varFileExtension = $varConfig->extension ?: $fileExtension;
                        $varFileName   = $this->getRandomName($varFileExtension);
                        $varFolderName = self::PICTURE_FOLDER . $pictureTypeId . '/' . substr($varFileName, 11, 2) . '/';
                        $varFilePath   = FCPATH . $varFolderName . $varFileName;
                        if (! is_dir($varFolderName)) {
                            mkdir($varFolderName, 0777, true);
                        }
                        $image->save($varFilePath);

                        if ($varConfig->watermark) {
                            $this->putWatermark($varFilePath, $varFileExtension);
                        }

                        $pictureVariation->path = '/' . $varFolderName . $varFileName;
                        $pictureVariation->filename = $varFileName;
                        $pictureVariation->extension = $varFileExtension;
                        $pictureVariation->size = ceil(filesize($varFilePath) / 1024);
                        $pictureVariation->height = $varConfig->height;
                        $pictureVariation->width = $varConfig->width;

                    } catch (ImageException $e) {
                        $responseData->message = $e->getMessage();
                        return $this->respond($responseData, $this->codes['updated']);
                    }

                } else { // it is a source picture
                    $pictureVariation->path = '/' . $folderName . $fileName;
                    $pictureVariation->filename = $fileName;
                    $pictureVariation->extension = $fileExtension;
                    $pictureVariation->source = 1;
                    $pictureVariation->size = ceil(filesize($filePath) / 1024);
                    $imageInfo = getimagesize($filePath);
                    $pictureVariation->height = $imageInfo[1] ?? null;
                    $pictureVariation->width = $imageInfo[0] ?? null;
                }

                if (! $this->pictureVariationModel->save($pictureVariation)) {
                    return $this->failValidationErrors($this->pictureVariationModel->errors());
                }
                $pictureVariation->id = $this->pictureVariationModel->getInsertID();

                // TODO to an object
                $variationsResponse[$varConfig->code] = [
                    'variation_id'   => $pictureVariation->id,
                    'type_code'      => $pictureType->code,
                    'variation_code' => $varConfig->code,
                    'path'           => $pictureVariation->path,
                    'filename'       => $pictureVariation->filename,
                    'extension'      => $pictureVariation->extension,
                    'width'          => $pictureVariation->width,
                    'height'         => $pictureVariation->height
                ];
            }

            $this->pictureModel->db->transCommit();

            $responseData->result = true;
            $responseData->picture = [
                'id'         => $picture->id,
                'variations' => $variationsResponse,
            ];

            return $this->respond($responseData, $this->codes['updated']);
        }

        $responseData->message = 'The file has already been moved.';
        return $this->respond($responseData, $this->codes['invalid_data']);
    }

    private function putWatermark(string $filePath, string $fileExtension): void
    {
        switch ($fileExtension) {
            case 'jpg':
            case 'jpeg': echo $filePath;
                $targetPicture    = imagecreatefromjpeg($filePath); break;
            case 'png':
                $targetPicture    = imagecreatefrompng($filePath); break;
            case 'gif':
                $targetPicture    = imagecreatefromgif($filePath); break;
            case 'webp':
                $targetPicture    = imagecreatefromwebp($filePath); break;
            default:
                return;
        }

        $watermarkPicture = imagecreatefrompng(self::WATERMARK_PATH);

        // Resize the watermark picture.
        list($widthTarget, $heightTarget) = getimagesize($filePath);
        list($widthWatermark, $heightWatermark) = getimagesize(self::WATERMARK_PATH);
        $heightWatermarkNew = 0.138 * $heightTarget;
        $widthWatermarkNew  = $widthWatermark * $heightWatermarkNew / $heightWatermark;

        $watermarkPictureResized = imagecreatetruecolor($widthWatermarkNew, $heightWatermarkNew);
        imagealphablending($watermarkPictureResized, false);
        imagesavealpha($watermarkPictureResized, true);

        imagecopyresampled($watermarkPictureResized, $watermarkPicture, 0, 0, 0, 0, $widthWatermarkNew, $heightWatermarkNew, $widthWatermark, $heightWatermark);

        // Put watermark and save the picture.
        $offset = round(10 * $heightTarget / 600);
        imagecopy(
            $targetPicture,
            $watermarkPictureResized,
            $offset,
            imagesy($targetPicture) - imagesy($watermarkPictureResized) - $offset,
            0,
            0,
            imagesx($watermarkPictureResized),
            imagesy($watermarkPictureResized)
        );

        imagedestroy($watermarkPicture);
        imagedestroy($watermarkPictureResized);

        switch ($fileExtension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($targetPicture, $filePath); break;
            case 'png':
                imagepng($targetPicture, $filePath); break;
            case 'gif':
                imagegif($targetPicture, $filePath); break;
            case 'webp':
                imagewebp($targetPicture, $filePath); break;
        }

    }

    private function getRandomName(string $extension): string
    {
        $extension = empty($extension) ? '' : '.' . $extension;
        return time() . '_' . bin2hex(random_bytes(10)) . $extension;
    }

}
