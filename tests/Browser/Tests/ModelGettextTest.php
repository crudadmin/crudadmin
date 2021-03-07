<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Models\Language;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Concerns\DropUploads;
use Gettext;
use Gettext\Generators\PhpArray;
use Gettext\Translations;

class ModelGettextTest extends BrowserTestCase
{
    use DropDatabase,
        DropUploads;

    protected function tearDown() : void
    {
        $this->uninstallAdmin();

        parent::tearDown();
    }

    private function getEnTranslates()
    {
        $language = Language::where('slug', 'en')->first();

        $translates = Translations::fromPoFile($language->poedit_po->basepath);

        return array_slice(PhpArray::generate($translates)['messages'][''], 1);
    }

    /** @test */
    public function test_gettext_editor_and_text_on_page()
    {
        $excepted = [
            '%d car' => ['my %d yellow car', 'my %d red cars'],
            'Translate 2' => ['translated text'],
            'Hello world' => [''],
            'title meta' => ['updated meta'],
            'Režim upravovania' => [''],
            'Administrácia webu' => [''],
        ];

        $this->browse(function (DuskBrowser $browser) use ($excepted) {
            $browser->openModelPage(Language::class)
                    ->resize(1920, 1080)
                    ->pause(1000)
                    ->click('[data-id="2"] [data-button="gettext"]')
                    ->waitForText(trans('admin::admin.gettext-update'))
                    ->pause(1100)
                    ->valueWithEvent('table td:contains("title meta") + td textarea', 'updated meta')
                    ->valueWithEvent('table td:contains("Translate 2") + td textarea', 'translated text')
                    ->valueWithEvent('table td:contains("%d car") + td textarea:nth-child(1)', 'my %d yellow car')
                    ->valueWithEvent('table td:contains("%d car") + td textarea:nth-child(2)', 'my %d red cars')
                    ->press(trans('admin::admin.gettext-save'))
                    ->waitUntilMissing('.gettext-table .modal-body');

            //Check if mo files has been successfully updated
            $translates = $this->getEnTranslates();

            $this->assertEquals($excepted, $translates);

            //Check if translates has been successfully updated without change
            //and nothing has been lost
            $browser->click('[data-id="2"] [data-button="gettext"]')
                    ->waitForText(trans('admin::admin.gettext-update'))
                    ->press(trans('admin::admin.gettext-save'))
                    ->waitUntilMissing('.gettext-table .modal-body');

            $translates = $this->getEnTranslates();
            $this->assertEquals($excepted, $translates);

            //Load first slovak translate
            $browser->pause(1100)
                    ->click('[data-id="1"] [data-button="gettext"]')
                    ->waitForText(trans('admin::admin.gettext-update'))
                    ->press(trans('admin::admin.gettext-save'))
                    ->waitUntilMissing('.gettext-table .modal-body');

            //Test en version
            $browser->visit('/en/?cars=2')
                    ->assertSee('Hello world')
                    ->assertSee('translated text')
                    ->assertSee('my 2 red cars')
                    ->assertSourceHas('<meta property="og:title" content="updated meta">');

            //Test sk version
            $browser->visit('/sk')->refresh()
                    ->assertPathIs('/')
                    ->assertSee('Translate 2')
                    ->assertSee('1 car')
                    ->assertSourceHas('<meta property="og:title" content="title meta">');
        });
    }
}
