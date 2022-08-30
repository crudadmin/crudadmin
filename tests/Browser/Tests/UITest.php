<?php

namespace Admin\Tests\Browser\Tests;

use Admin;
use Admin\Tests\App\User;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\App\Models\Articles\Article;

class UITest extends BrowserTestCase
{
    /** @test */
    public function test_assets_version_check_alert()
    {
        $this->browse(function (DuskBrowser $browser) {
            file_put_contents(Admin::getAssetsVersionPath('version.txt'), 'wrong-version');

            $browser->openModelPage(Article::class)
                    ->assertSee('php artisan admin:update')
                    ->assertSee('wrong-version');
        });
    }
}
