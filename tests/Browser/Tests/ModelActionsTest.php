<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\App\Models\Tree\Model1;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\Concerns\SeedTrait;
use Admin\Tests\App\Models\Articles\Article;

class ModelActionsTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    /** @test */
    public function test_publishable_button()
    {
        $this->createArticleMoviesList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)

                    //Check if is unpublished
                    ->click('[data-id="10"] [data-button="publishable"]')
                    ->whenAvailable('[data-id="10"] [data-button="publishable"][data-published="false"]', function(){
                        $this->assertNull(Article::find(10));
                    })

                    //Check if has been back published
                    ->click('[data-id="10"] [data-button="publishable"]')
                    ->whenAvailable('[data-id="10"] [data-button="publishable"][data-published="true"]', function(){
                        $this->assertTrue(Article::find(10)->published_at ? true : false);
                    });
        });
    }

    /** @test */
    public function test_publishable_multiple_items_button()
    {
        $this->createArticleMoviesList();

        $article = Article::find(10);

        $this->browse(function (DuskBrowser $browser) use ($article) {
            $browser->openModelPage(Article::class)

                    //Check which item we want delete
                    ->click('tr[data-id="10"]')
                    ->click('tr[data-id="7"]')
                    ->click('[data-action-list] button')
                    ->jsClick('[data-action-list] a:contains("'.trans('admin::admin.publish-toggle').'")')
                    ->waitFor('[data-id="10"] [data-button="publishable"][data-published="false"]');
        });

        //Check if row has been removed from table and from db
        $this->assertNull(Article::find(10));
        $this->assertNull(Article::find(7));
    }

    /** @test */
    public function test_info_button()
    {
        $this->createArticleMoviesList();

        $article = Article::find(10);

        $this->browse(function (DuskBrowser $browser) use ($article) {
            $browser->openModelPage(Article::class)

                    //Check if is unpublished
                    ->click('[data-id="10"] [data-button="show"]')->pause(100)
                    ->assertSeeIn('.modal .modal-title', trans('admin::admin.row-info-n').' 10')
                    ->assertSeeIn('.modal .modal-body', trans('admin::admin.created-at').': '.$article->created_at->format('d.m.Y H:i'))
                    ->assertSeeIn('.modal .modal-body', trans('admin::admin.last-change').': '.$article->updated_at->format('d.m.Y H:i'))
                    ->assertSeeIn('.modal .modal-body', trans('admin::admin.published-at').': '.$article->published_at->format('d.m.Y H:i'));
        });
    }

    /** @test */
    public function test_delete_item_button()
    {
        $this->createArticleMoviesList();

        $article = Article::find(10);

        $this->browse(function (DuskBrowser $browser) use ($article) {
            $browser->openModelPage(Article::class)

                    //Check if is unpublished
                    ->click('[data-id="10"] [data-button="delete"]')
                    ->jsClick('.modal .modal-footer button:contains("'.trans('admin::admin.accept').'")')
                    ->waitUntilMissing('[data-id="10"]');

            //Check if row has been removed from table and from db
            $this->assertArrayNotHasKey(10, $browser->getRows(Article::class));
            $this->assertNull(Article::find(10));
        });
    }

    /** @test */
    public function test_delete_multiple_items_button()
    {
        $this->createArticleMoviesList();

        $article = Article::find(10);

        $this->browse(function (DuskBrowser $browser) use ($article) {
            $browser->openModelPage(Article::class)

                    //Check which item we want delete
                    ->click('tr[data-id="10"]')
                    ->click('tr[data-id="7"]')
                    ->click('[data-action-list] button')->pause(50)
                    ->jsClick('[data-action-list] a:contains("'.trans('admin::admin.delete').'")')->pause(50)
                    ->jsClick('.modal .modal-footer button:contains("'.trans('admin::admin.accept').'")')->pause(50);

            //Check if row has been removed from table and from db
            $this->assertArrayNotHasKey(10, $browser->getRows(Article::class));
            $this->assertArrayNotHasKey(7, $browser->getRows(Article::class));
            $this->assertNull(Article::find(10));
            $this->assertNull(Article::find(7));
        });
    }

    /** @test */
    public function test_multiple_custom_simple_button()
    {
        Model1::create(['field1' => 'c', 'field2' => 'd', 'field3' => 'e', 'field4' => 'f']);
        Model1::create(['field1' => 'a', 'field2' => 'b', 'field3' => 'c', 'field4' => 'd']);

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Model1::class)

                    //Click multiple items and then press button action
                    ->click('tr[data-id="1"]')
                    ->click('tr[data-id="2"]')
                    ->click('[data-action-list] button')
                    ->jsClick('[data-action-list] a:contains("SimpleMultipleButton")')
                    ->whenAvailable('.modal .modal-footer', function() use($browser) {
                        $browser->jsClick('.modal .modal-footer button:contains("Zatvoriť")')->pause(50);
                    });

            //Check if action has been processed
            //and table rewrited with actual data
            $browser->assertColumnRowData(Model1::class, 'field3', [6, 6]);
        });
    }

    /** @test */
    public function test_custom_simple_button()
    {
        $row = Model1::create([
            'field1' => 'test item',
            'field2' => '<a href="#">Text</a>',
            'field3' => '<a href="#">Text</a>',
            'field4' => 'column 4 longest text more than 20 chars...',
        ]);

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->openModelPage(Model1::class)

                    //Click on button action
                    ->click('[data-id="1"] [data-button="action-SimpleButton"]')->pause(100)
                    ->jsClick('.modal .modal-footer button:contains("Zatvoriť")')->pause(50);

            //Check if action has been processed
            //and table rewrited with actual data
            $browser->assertColumnRowData(Model1::class, 'field3', [5]);
        });
    }

    /** @test */
    public function test_custom_question_button()
    {
        $row = Model1::create([
            'field1' => 'test item',
            'field2' => '<a href="#">Text</a>',
            'field3' => '<a href="#">Text</a>',
            'field4' => 'column 4 longest text more than 20 chars...',
        ]);

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->openModelPage(Model1::class)

                    //Click on button action
                    ->click('[data-id="1"] [data-button="action-QuestionButton"]')->pause(100)
                    ->assertSeeIn('.modal .modal-body', 'Are you sure?')
                    ->jsClick('.modal .modal-footer button:contains("Zatvoriť")')->pause(50);

            //Check if action has not been processed
            //and table row is not rewrited with actual data
            $this->assertNull(Model1::where('field2', 10)->first());
            $browser->assertColumnRowDataNotEquals(Model1::class, 'field2', [10]);

            //Click and accept alert button, then check if data has been processed
            $browser->click('[data-id="1"] [data-button="action-QuestionButton"]')->pause(100)
                    ->jsClick('.modal .modal-footer button:contains("Potvrdiť")')->pause(50);
            $browser->assertColumnRowData(Model1::class, 'field2', [10]);
        });
    }

    /** @test */
    public function test_custom_question_with_template_button()
    {
        $row = Model1::create([
            'field1' => 'test item',
            'field2' => '<a href="#">Text</a>',
            'field3' => '<a href="#">Text</a>',
            'field4' => 'column 4 longest text more than 20 chars...',
        ]);

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->openModelPage(Model1::class)

                    //Click on button action
                    ->click('[data-id="1"] [data-button="action-TemplateButton"]')->pause(100)

                    //Check if template modal renders correctly
                    ->assertSeeIn('.modal-body label', 'How are you? This is my custom component.')

                    //Type value into field and check if vuejs interactivity works
                    ->type('.modal .modal-body input', 'good, awesome.')
                    ->assertSeeIn('.modal .modal-body h2', 'I have good, awesome. mood.')

                    //Accept and check if success message appears
                    ->jsClick('.modal .modal-footer button:contains("Potvrdiť")')->pause(50)
                    ->assertSeeIn('.modal .modal-body', 'Your custom template action is done!')

                    //Check if button has ben processed and data changed
                    ->assertColumnRowData(Model1::class, 'field4', ['good, awesome.']);
        });
    }
}
