<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Maker;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function Symfony\Component\String\u;

final class ClassMaker
{
    private $kernel;
    private $projectDir;
    /** @var Filesystem */
    private $fs;

    public function __construct(KernelInterface $kernel, string $projectDir)
    {
        $this->kernel = $kernel;
        $this->projectDir = $projectDir;
        $this->fs = new Filesystem();
    }

    /**
     * @return The path of the created file (relative to the project dir)
     */
    public function make(string $generatedFilePathPattern, string $skeletonName, array $skeletonParameters): string
    {
        $skeletonPath = sprintf('%s/%s', $this->kernel->locateResource('@EasyAdminBundle/Resources/skeleton'), $skeletonName);
        $generatedFileRelativeDir = u($generatedFilePathPattern)->beforeLast('/')->trimEnd('/');
        $generatedFileNamePattern = u($generatedFilePathPattern)->afterLast('/')->trimStart('/');

        $generatedFileDir = sprintf('%s/%s', $this->projectDir, $generatedFileRelativeDir);
        $this->fs->mkdir($generatedFileDir);
        if (!$this->fs->exists($generatedFileDir)) {
            throw new \RuntimeException(sprintf('The "%s" directory does not exist and cannot be created, so the class generated by this command cannot be created.', $generatedFileDir));
        }

        // first, try to create a file name without any autoincrement index in it
        $generatedFileName = $generatedFileNamePattern->replace('%d', '');
        $i = 1;
        while ($this->fs->exists(sprintf('%s/%s', $generatedFileDir, $generatedFileName))) {
            $generatedFileName = $generatedFileNamePattern->replace('%d', ++$i);
        }
        $generatedFilePath = sprintf('%s/%s', $generatedFileDir, $generatedFileName);

        $skeletonParameters = array_merge($skeletonParameters, [
            'class_name' => u($generatedFileName)->beforeLast('.php')->toString(),
            'namespace' => 'App\\Controller\\Admin',
        ]);

        $this->fs->dumpFile($generatedFilePath, $this->renderSkeleton($skeletonPath, $skeletonParameters));

        return u($generatedFilePath)->after($this->projectDir)->trim('/')->toString();
    }

    private function renderSkeleton(string $filePath, array $parameters): string
    {
        ob_start();
        extract($parameters, EXTR_SKIP);
        include $filePath;

        return ob_get_clean();
    }
}
