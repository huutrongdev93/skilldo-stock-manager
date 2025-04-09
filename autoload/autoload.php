<?php
namespace Skdepot;
use Admin;
use Storage;
use Str;

class ClassAutoLoad {

    static $storage = null;

    protected string $name = SKDEPOT_NAME;

    protected array $files = [];

    protected array $onlyAdmin  = [];

    protected array $onlyClient = [];

    protected array $folders = [];

    protected bool $isAdmin = false;

    protected array $filesAutoLoad = [];

    public function __construct()
    {
        add_filter('cms_class_autoloader_map', [$this, 'autoLoadClass'], 10, 2);

        $autoLoads = $this->storage()->json($this->name.'/autoload/autoload.json');

        $this->files = $autoLoads['files'] ?? [];

        $this->onlyAdmin['files'] = $autoLoads['only-admin']['files'] ?? [];

        foreach ($this->onlyAdmin['files'] as $index => $file)
        {
            $this->onlyAdmin['files'][$index] = $this->name.'/'.$file;
        }

        $this->onlyClient['files'] = $autoLoads['only-client']['files'] ?? [];

        foreach ($this->onlyClient['files'] as $index => $file)
        {
            $this->onlyClient['files'][$index] = $this->name.'/'.$file;
        }

        $this->folders = $autoLoads['folders'] ?? [];

        $this->onlyAdmin['folders'] = $autoLoads['only-admin']['folders'] ?? [];

        $this->onlyClient['folders'] = $autoLoads['only-client']['folders'] ?? [];

        $this->isAdmin = Admin::is();

        $this->autoLoad();
    }

    public function autoLoadClass(array $classMaps, string $class): array
    {
        return $classMaps;
    }

    public function storage(): ?\Illuminate\Filesystem\FilesystemAdapter
    {
        if(self::$storage === null)
        {
            self::$storage = Storage::disk('plugin');
        }

        return self::$storage;
    }

    public function autoLoadFolder(string $path): void
    {
        if(!$this->isAdmin)
        {
            if(in_array($path, $this->onlyAdmin['folders']))
            {
                return;
            }
        }
        else
        {
            if(in_array($path, $this->onlyClient['folders']))
            {
                return;
            }
        }

        $files = $this->storage()->allFiles($this->name.'/'.$path);

        foreach ($files as $index => $file)
        {
            if(!Str::endsWith($file, '.php'))
            {
                unset($files[$index]);
            }
        }

        foreach ($files as $file)
        {
            if(!$this->isAdmin)
            {
                if(in_array($file, $this->onlyAdmin['files']))
                {
                    continue;
                }
            }
            else
            {
                if(in_array($file, $this->onlyClient['files']))
                {
                    continue;
                }
            }

            $this->filesAutoLoad[md5($file.uniqid())] = $file;
        }
    }

    public function autoloadFile(): void
    {
        if(!empty($this->files))
        {
            foreach ($this->files as $file)
            {
                if(!$this->isAdmin)
                {
                    if(in_array($file, $this->onlyAdmin['files']))
                    {
                        continue;
                    }
                }
                else
                {
                    if(in_array($file, $this->onlyClient['files']))
                    {
                        continue;
                    }
                }

                $this->filesAutoLoad[md5($file).uniqid()] = $this->name.'/'.$file;
            }
        }
    }

    public function createMap(): void
    {
        if(!empty($this->folders))
        {
            foreach ($this->folders as $folder)
            {
                $this->autoLoadFolder($folder);
            }
        }

        $this->autoloadFile();

        $lines = [
            '<?php',
            '$vendorDir = dirname(__FILE__, 2);',
            '$baseDir = dirname($vendorDir);',
            'return [',
        ];

        if(have_posts($this->filesAutoLoad))
        {
            foreach ($this->filesAutoLoad as $key => $file)
            {
                $lines[] ='   \''.str_replace('.', '_', $key).'\' => $baseDir. \'/'.$file.'\',';
            }
        }

        $lines[] = '];';

        $lines = implode("\n", $lines);

        if($this->isAdmin)
        {
            $this->storage()->put($this->name.'/autoload/autoload_admin.php', $lines);
        }
        else
        {
            $this->storage()->put($this->name.'/autoload/autoload_client.php', $lines);
        }
    }

    public function autoLoad(): void
    {
        $baseDir = FCPATH.VIEWPATH.'plugins/'.$this->name;

        if((($this->isAdmin && !file_exists($baseDir.'/autoload/autoload_admin.php')) || (!$this->isAdmin && !file_exists($baseDir.'/autoload/autoload_client.php'))))
        {
            $this->createMap();
        }

        if($this->isAdmin)
        {
            $files = include_once $baseDir.'/autoload/autoload_admin.php';
        }
        else
        {
            $files = include_once $baseDir.'/autoload/autoload_client.php';
        }

        if(!empty($files))
        {
            foreach ($files as $key => $file)
            {
                include_once $file;
            }
        }
    }
}

new ClassAutoLoad();