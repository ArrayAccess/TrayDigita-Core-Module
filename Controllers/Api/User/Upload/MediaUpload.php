<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers\Api\User\Upload;

use ArrayAccess\TrayDigita\App\Modules\Media\Media;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\UserAPI;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers\AbstractApiController;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Exceptions\InvalidArgument\UnsupportedArgumentException;
use ArrayAccess\TrayDigita\Exceptions\Logical\OutOfRangeException;
use ArrayAccess\TrayDigita\Exceptions\Runtime\RuntimeException;
use ArrayAccess\TrayDigita\Http\Code;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Uploader\Exceptions\ContentRangeIsNotFulFilledException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

#[UserAPI('/upload')]
class MediaUpload extends AbstractApiController
{
    use TranslatorTrait;

    protected string $name = 'file';

    /** @noinspection DuplicatedCode */
    #[Any('/media')]
    public function upload(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        /**
         * Example api
         */
        $uploader = $this
            ->getModule(Media::class)
            ?->getUserUpload();
        if (!$uploader) {
            return $this->renderJson(
                Code::SERVICE_UNAVAILABLE,
                'Uploader is not ready',
                $response
            );
        }

        /**
         * @var UploadedFileInterface $file
         */
        $file = $request->getUploadedFiles()[$this->name]??null;
        if (!$file) {
            return $this->renderJson(
                Code::PRECONDITION_REQUIRED,
                'No file uploaded',
                $response
            );
        }

        $clientFileName = $file->getClientFilename();
        if (!$clientFileName) {
            return $this->renderJson(
                Code::PRECONDITION_FAILED,
                'Invalid file name',
                $response
            );
        }

        // @todo remove user
        $users = $this->getModule(Users::class);
        $user = $users->getUserById(1);
        try {
            $metaData = $uploader->uploadPublic(
                $request,
                $file,
                $user
            );
            $response  = $metaData->progress->appendResponseHeader($response);
            $meta = $metaData->toArray(true);
            if (!$metaData->finished) {
                return $this->renderJson(
                    $metaData->progress->processor->isNewRequestId
                        ? Code::CREATED
                        : Code::ACCEPTED,
                    $meta,
                    $response
                );
            }
            return $this->renderJson(
                Code::OK,
                $meta,
                $response
            );
        } catch (UnsupportedArgumentException $e) {
            return $this->renderJson(
                Code::PRECONDITION_FAILED,
                $e->getMessage(),
                $response
            );
        } catch (ContentRangeIsNotFulFilledException|OutOfRangeException $e) {
            return $this->renderJson(
                Code::REQUESTED_RANGE_NOT_SATISFIABLE,
                $e->getMessage(),
                $response
            );
        } catch (RuntimeException $e) {
            return $this->renderJson(
                Code::NOT_IMPLEMENTED,
                $e->getMessage(),
                $response
            );
        } catch (Throwable $e) {
            return $this->renderJson(
                Code::INTERNAL_SERVER_ERROR,
                $e,
                $response
            );
        }
    }
}
