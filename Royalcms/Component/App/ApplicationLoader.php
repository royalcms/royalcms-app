<?php


namespace Royalcms\Component\App;


use Royalcms\Component\App\Bundles\AppBundle;
use Royalcms\Component\Support\Facades\File as RC_File;

class ApplicationLoader
{

    protected $app_roots = [];

    public function __construct($app_roots = null)
    {
        if (!empty($app_roots)) {
            $this->app_roots = $app_roots;
        }
    }

    public function addAppRootPath($dir)
    {
        $this->app_roots[] = $dir;
    }

    /**
     * @return \Illuminate\Support\Collection|\Royalcms\Component\Support\Collection
     */
    public function loadAppsWithRoot()
    {
        $app_roots = collect($this->app_roots)->map(function ($app_root) {

            if (file_exists($app_root)) {
                $apps_dir = RC_File::directories($app_root);

                $apps = collect($apps_dir)->map(function ($path) {
                    $dir = basename($path);
                    $bundle = new AppBundle($dir);
                    if (! $bundle->getIdentifier()) {
                        return null;
                    }
                    return $bundle;
                })->filter();

                return $apps;
            }

            return null;
        })->filter();

        return $app_roots;
    }

    /**
     * @return \Illuminate\Support\Collection|\Royalcms\Component\Support\Collection
     */
    public function loadApps()
    {
        $app_roots = $this->loadAppsWithRoot();
        $app_roots = $app_roots->collapse()->sort(array(__CLASS__, '_sort_uname_callback'));

        return $app_roots;
    }


    public function loadAppsWithAlias()
    {
        $apps = $this->loadApps();

        $apps = $apps->mapWithKeys(function ($bundle) {
            $data[$bundle->getAlias()] = $bundle;

            if ($bundle->getAlias() != $bundle->getDirectory()) {
                $data[$bundle->getDirectory()] = $bundle;
            }

            return $data;
        });

        return $apps;
    }

    public function loadAppsWithIdentifier()
    {
        $apps = $this->loadApps();

        $apps = $apps->mapWithKeys(function ($bundle) {
            $data[$bundle->getIdentifier()] = $bundle;
            return $data;
        });

        return $apps;
    }


    public function toArray($apps)
    {
        $apps = $apps->map(function ($bundle) {
            return $bundle->toArray();
        })->toArray();

        return $apps;
    }

    /**
     * Callback to sort array by a 'Name' key.
     *
     * @since 3.2.0
     * @access private
     */
    public static function _sort_uname_callback($a, $b)
    {
        return strnatcasecmp( $a->getDirectory(), $b->getDirectory() );
    }

}