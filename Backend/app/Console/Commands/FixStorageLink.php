<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixStorageLink extends Command
{
    protected $signature = 'storage:fix';
    protected $description = 'Fix broken storage symlink';

    public function handle(): void
    {
        $publicStorage = public_path('storage');
        
        // Remove existing broken link
        if (is_link($publicStorage) || file_exists($publicStorage)) {
            $this->info('Removing existing storage link...');
            File::deleteDirectory($publicStorage);
            @unlink($publicStorage);
        }
        
        // Create new symlink
        $this->info('Creating storage symlink...');
        $target = storage_path('app/public');
        $link = $publicStorage;
        
        if (symlink($target, $link)) {
            $this->info('✅ Storage symlink created successfully!');
        } else {
            $this->error('❌ Failed to create symlink. Try running with sudo or check permissions.');
            return;
        }
        
        // Fix permissions
        $this->info('Setting permissions...');
        chmod(storage_path('app/public'), 0755);
        chmod($link, 0755);
        
        // Clear cache
        $this->call('cache:clear');
        $this->call('config:clear');
        
        $this->info('✅ Storage fix complete!');
        $this->info('');
        $this->info('Test URL: ' . asset('storage/avatars/'));
    }
}
