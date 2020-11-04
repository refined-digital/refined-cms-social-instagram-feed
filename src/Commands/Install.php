<?php

namespace RefinedDigital\Social\InstagramFeed\Commands;

use Illuminate\Console\Command;
use Validator;
use Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refinedCMS:install-social-instagram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs social instagram';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createSymLink();
        $this->info('Social Instagram Feed has been successfully installed');
    }


    protected function createSymLink()
    {
        $this->output->writeln('<info>Creating Symlink</info>');
        try {
            $link = public_path('vendor/');
            $target = '../../../vendor/refineddigital/cms-social-instagram-feed/assets/';

            // create the directories
            if (!is_dir($link)) {
                mkdir($link);
            }
            $link .= 'refined/';
            if (!is_dir($link)) {
                mkdir($link);
            }
            $link .= 'instagram-feed';
            if (! windows_os()) {
                return symlink($target, $link);
            }

            $mode = is_dir($target) ? 'J' : 'H';

            exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
        } catch(\Exception $e) {
            $this->output->writeln('<error>Failed to install symlink</error>');
        }
    }
}
