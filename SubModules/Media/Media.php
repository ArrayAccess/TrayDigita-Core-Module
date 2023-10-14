<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Media;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Media\Traits\MediaFilterTrait;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Media\Traits\MediaPathTrait;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Media\Uploader\AdminUpload;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Media\Uploader\UserUpload;
use ArrayAccess\TrayDigita\Uploader\Chunk;
use ArrayAccess\TrayDigita\Uploader\StartProgress;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class Media extends CoreSubmoduleAbstract
{
    use MediaPathTrait,
        MediaFilterTrait;

    protected ?ServerRequestInterface $request = null;

    protected Chunk $chunk;

    protected ?DataServe $dataServe = null;

    protected ?AdminUpload $adminUpload = null;

    protected ?UserUpload $userUpload = null;

    protected string $name = 'Media Manager';

    public function getName(): string
    {
        return $this->translate(
            'Media Manager',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Module to make application support media & file attachments',
            context: 'module'
        );
    }

    protected function doInit(): void
    {
        $this->doFilterPath();
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function getDataServe(): DataServe
    {
        return $this->dataServe ??= new DataServe($this);
    }

    public function getChunk(): Chunk
    {
        return $this->chunk ??= ContainerHelper::use(Chunk::class, $this->getContainer());
    }

    /**
     * @param UploadedFileInterface $uploadedFile
     * @param ServerRequestInterface $request
     * @return StartProgress
     */
    public function upload(
        UploadedFileInterface $uploadedFile,
        ServerRequestInterface $request
    ): StartProgress {
        return StartProgress::create(
            $this->getChunk(),
            $uploadedFile,
            $request
        );
    }

    public function getAdminUpload(): AdminUpload
    {
        return $this->adminUpload ??= new AdminUpload($this);
    }

    public function getUserUpload(): UserUpload
    {
        return $this->userUpload ??= new UserUpload($this);
    }
}
