<?php

namespace Admin\Tests\Browser\Tests;

use Gettext;
use Gettext\Translations;
use Admin\Models\Language;
use Gettext\Generators\PhpArray;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;

class ModelGettextTest extends BrowserTestCase
{
    use DropDatabase;

    protected function tearDown() : void
    {
        $this->uninstallAdmin();

        parent::tearDown();
    }

    private function getEnTranslates()
    {
        $language = Language::where('slug', 'en')->first();

        $mo_file = Gettext::getLocalePath('en_US', $language->poedit_mo);

        $translates = Translations::fromMoFile($mo_file);

        return array_slice(PhpArray::generate($translates)['messages'][''], 1);
    }

    /** @test */
    public function test_gettext_editor_and_text_on_page()
    {
        $excepted = [
            '%d car' => ['my %d yellow car', 'my %d red cars'],
            'Translate 2' => ['translated text'],
            'title meta' => ['updated meta'],
        ];

        $this->browse(function (DuskBrowser $browser) use ($excepted) {
            $browser->openModelPage(Language::class)
                    ->click('[data-id="2"] [data-button="gettext"]')
                    ->waitForText(trans('admin::admin.gettext-update'))
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

            $browser->visit('/en/?cars=2')
                    ->assertSee('Hello world')
                    ->assertSee('translated text')
                    ->assertSee('my 2 red cars')
                    ->assertSourceHas('<meta property="og:title" content="updated meta">');
        });
    }
}
