<?php

namespace RefinedDigital\Social\InstagramFeed\Commands;

use Illuminate\Console\Command;

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->createSymLink();
        $this->info('Social Instagram Feed has been successfully installed');
        $this->line('Vite sites can import vendor/refineddigital/cms-social-instagram-feed/resources/js/instagram-feed.js instead of loading the public script, see the README.');

        return self::SUCCESS;
    }

    /**
     * Links public/vendor/refined/instagram-feed to the package's prebuilt assets, for sites that load the script tag.
     */
    protected function createSymLink(): void
    {
        $this->output->writeln('<info>Creating Symlink</info>');

        $dir = public_path('vendor/refined');
        $link = $dir.'/instagram-feed';
        $target = '../../../vendor/refineddigital/cms-social-instagram-feed/assets/';

        if (is_link($link) || file_exists($link)) {
            $this->output->writeln(' <comment>'.$link.' already exists, skipping</comment>');

            return;
        }

        try {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (! windows_os()) {
                symlink($target, $link);

                return;
            }

            $absoluteTarget = base_path('vendor/refineddigital/cms-social-instagram-feed/assets');
            exec("mklink /J \"{$link}\" \"{$absoluteTarget}\"");
        } catch (\Throwable $e) {
            $this->output->writeln('<error>Failed to install symlink: '.$e->getMessage().'</error>');
        }
    }
}
