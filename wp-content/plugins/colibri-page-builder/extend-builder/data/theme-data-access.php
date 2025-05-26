<?php

namespace ExtendBuilder;

use function file_exists;
use function get_option;
use function rename;
use function str_replace;

class OptionThemeData
{
    const OPTION_NAME = 'extend_builder_theme';

    public function get()
    {
        return get_option(self::OPTION_NAME, []);
    }

    public function set($value)
    {
        update_option(self::OPTION_NAME, $value, 'no');
    }

    public function backup() {
        update_option(self::OPTION_NAME."_".time(), $this->get(), 'no');
    }
}

class FileThemeData
{
    const FS_OPTION_NAME = 'colibri_page_builder_use_fs';
    const FS_OPTION_FS_ERROR = 'colibri_page_builder_fs_error';

    const PREFIX = '<?php //';
    private $files_dir = "colibri";

    public  function canWriteToFs()
    {
        $file_path = $this->file_path('test-'.time());
        $write = file_put_contents($file_path, 1);
        if ($write !== false){
            unlink($file_path);
            return true;
        }
        return false;
    }

    public function generateUniqId() {
        return dechex(mt_rand())."-".dechex(mt_rand())."-".dechex(mt_rand())."-".dechex(mt_rand());
    }

    public  function enable()
    {
        $option_value = get_option(FileThemeData::FS_OPTION_NAME, false);
        if ($option_value === false) {
            $can_write = $this->canWriteToFs();
            update_option(FileThemeData::FS_OPTION_NAME, $can_write ? 'c-'.$this->generateUniqId() : self::FS_OPTION_FS_ERROR);
        }
    }

    public  function disable()
    {
        $option_value = get_option(FileThemeData::FS_OPTION_NAME, false);
        if ($option_value !== false) {
            delete_option(FileThemeData::FS_OPTION_NAME);
        }
    }

    function isMigrated() {
        return $this->file_name() !== false && file_exists($this->file_path());
    }

    function file_name()
    {
        return get_option(FileThemeData::FS_OPTION_NAME, false);
    }

    function oldFilePath($name = "")
    {
        return $this->file_path($name) . ".php";
    }

    function file_path($name = "")
    {
        $dir_path = self::uploads_dir() . '/' . $this->files_dir;

        if (!is_dir($dir_path)) {
            wp_mkdir_p($dir_path);
        }

        if (!$name) {
            $name = $this->file_name();
        }

        return $dir_path . "/" . $name;
    }

    function uploads_dir()
    {
        $upload_data = wp_upload_dir(null, false);
        return $upload_data['basedir'];
    }


    function protectFolder()
    {
        try{
            $index_file = $this->file_path('index.html');
            $htaccess_file = $this->file_path('.htaccess');
            $webconfig_file = $this->file_path('web.config');

            if (!is_file($index_file)) {
                file_put_contents($index_file,'');
            }

            if (!is_file($htaccess_file)) {
                file_put_contents($htaccess_file,'deny from all');
            }

            if (!is_file($webconfig_file)) {
                file_put_contents($webconfig_file,'<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>');
            }
        } catch ( Exception $e ) {
        }
    }

    public function maybeMoveToNewFile(){
        $old_file = $this->oldFilePath();
        $new_file = $this->file_path();
        if (file_exists($old_file) && !file_exists($new_file)) {
            $old_content = $this->get($old_file);
            $this->set($old_content);
        }
    }

    public function get($file_path_ = false)
    {
        $file_path = $file_path_ ? $file_path_ : $this->file_path();
        $content = @file_get_contents($file_path);
        if ($content !== false) {
            $content = str_replace(self::PREFIX, '', $content);
            return json_decode($content, true);
        }
        return [];
    }

    public function cleanBackups()
    {
        $backups = glob($this->file_path("*.bkp"));
        $max = 19;
        $count = count($backups);
        if ($backups && $count > $max) {
            usort( $backups, function( $a, $b ) { return filemtime($a) - filemtime($b); } );
            for ($i = 0; $i < ($count - $max); $i++) {
                unlink($backups[$i]);
            }
        }
    }
    public function backup()
    {
        try {
            if ($this->isMigrated()) {
                $this->cleanBackups();
                file_put_contents($this->file_path('c-' . $this->generateUniqId() . ".bkp"), json_encode($this->get()));
            }
        } catch ( Exception $e ) {
        }
    }

    public function set($value)
    {
        try {
            $this->protectFolder();
            $this->backup();
            file_put_contents($this->file_path(), self::PREFIX . json_encode($value));
        } catch ( Exception $e ) {
        }
    }
}

class ThemeDataAccess
{
    public $option;
    public $fs;
    private static $instance;

    public static function getInstance()
    {
        if ( ! self::$instance ) {
            self::$instance = new ThemeDataAccess();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->option = new OptionThemeData();
        $this->fs = new FileThemeData();
    }

    public static function maybeUpdateUrls()
    {
        $value = self::getInstance()->get();
        $updated_data = maybe_update_site_urls($value);
        if ($updated_data !== false) {
            self::getInstance()->set($updated_data);
        }
    }

    public static function maybeEnableFs()
    {
        if (self::shouldTryToEnableFs()) {
            self::getInstance()->enableFS();
        }
    }

    public static function shouldTryToEnableFs()
    {
        $fs_option_value = get_option(FileThemeData::FS_OPTION_NAME, false);
        return $fs_option_value === false;
    }

    public static function canUseFs()
    {
        $fs_option_value = get_option(FileThemeData::FS_OPTION_NAME, false);
        return $fs_option_value !== false && $fs_option_value !== FileThemeData::FS_OPTION_FS_ERROR;
    }

    public function enableFS()
    {
        $this->fs->enable();
    }

    public function moveFromOptionToFile()
    {
        if (self::canUseFs()) {
            $value = $this->option->get();
            $this->fs->set($value);
        }
    }

    public function moveFromFileToOption()
    {
        if (self::canUseFs()) {
            $value = $this->fs->get();
            $this->option->set($value);
            $this->fs->disable();
        }
    }

    public function get()
    {
        if (self::canUseFs()) {
            $this->fs->maybeMoveToNewFile();
            if (!$this->fs->isMigrated()) {
                $value = $this->option->get();
                $this->fs->set($value);
            } else {
                $value = $this->fs->get();
            }
        } else {
            $value = $this->option->get();
        }
        return $value;
    }

    public function backup() {
        $this->option->backup();
    }

    public function set($value)
    {
        if (self::canUseFs()) {
            $this->fs->set($value);
        }
        // fallback //
        $this->option->set($value);
    }
}

