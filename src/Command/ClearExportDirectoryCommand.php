<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Command;

use FINDOLOGIC\PlentyMarketsRestExporter\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ClearExportDirectoryCommand extends ClearDirectoryBaseCommand
{
    protected static $defaultName = 'clear:export';

    public function __construct(string $name = null, ?Filesystem $fileSystem = null, ?Finder $finder = null)
    {
        parent::__construct($name, $fileSystem, $finder);

        $this->setDirectory(Utils::env('EXPORT_DIR', __DIR__ . '/../../var/export'));
    }

    protected function configure()
    {
        $this->setDescription('Clears the current export data in the "export" directory.')
            ->setHelp('This command empties the "export" folders contents.');
    }
}
