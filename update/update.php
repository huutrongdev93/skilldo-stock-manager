<?php
namespace Stock;

use Option;
use Plugin;

class Updater
{
    protected string $version;

    protected ?string $currentVersion;

    protected array $timeline;

    protected $update;

    public function __construct()
    {
        $this->version = Plugin::getInfo(STOCK_NAME)['version'];

        $this->currentVersion = Option::get('stock_manager_version');

        if(empty($this->currentVersion))
        {
            $this->currentVersion = '1.0.0';
        }

        $this->timeline = ['1.1.0', '1.3.0', '2.0.0', '3.0.0'];
    }

    public function setUpdate($version): void
    {
        $this->update = null;

        $className = 'UpdateVersion'.str_replace('.', '', $version);

        if(!class_exists('Stock\\Update\\'.$className))
        {
            if(file_exists(__DIR__.'/'. $version.'/UpdateVersion.php'))
            {
                require_once __DIR__ .'/'. $version.'/UpdateVersion.php';
            }
        }

        if(class_exists('Stock\\Update\\'.$className))
        {
            $className = 'Stock\\Update\\'.$className;

            $this->update = new $className();
        }
    }

    public function checkForUpdates(): void
    {
        if (version_compare($this->version, $this->currentVersion, '>'))
        {
            foreach ($this->timeline as $version)
            {
                if(version_compare($version, $this->currentVersion) == 1)
                {
                    $this->setUpdate($version);

                    if(!empty($this->update))
                    {
                        $this->update->run();

                        Option::update('stock_manager_version', $version);
                    }
                }
            }
        }
        else
        {
            Plugin::setCheckUpdate(STOCK_NAME, $this->version);
        }
    }
}