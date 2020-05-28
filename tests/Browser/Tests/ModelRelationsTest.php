<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\App\Models\Articles\Article;
use Admin\Tests\App\Models\Articles\ArticlesComment;

class ModelRelationsTest extends BrowserTestCase
{
    use DropDatabase;

    /*
     * Drag and drop, does not work drag&drop as axcepted
     */
    public function test_belongs_to_model_relations_view()
    {
        //Create articles
        factory(Article::class, 5)->create();
        ArticlesComment::insert([
            ['article_id' => 1, 'name' => 'comment 1'],
            ['article_id' => 1, 'name' => 'comment 2'],
            ['article_id' => 1, 'name' => 'comment 3'],
            ['article_id' => 2, 'name' => 'comment 1 of article 2'],
        ]);

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)

                    //Open row, and load related model data, also check if count of loaded data is correct
                    ->openRow(1)
                    ->waitForElement('[data-tabs][data-model="articles_comments"]:contains("Comments (3)"):visible')
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (3)')

                    //Click on related model and check given data
                    ->click('[data-tabs][data-model="articles_comments"]')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [3, 2, 1])->pause(300)

                    //Fill form and save new related row.
                    ->fillForm(ArticlesComment::class, ['name' => 'new related comment'])
                    ->submitForm()->closeAlert()

                    //Check if new row has been given into table
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (4)')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [5, 3, 2, 1])

                    //Open created related row, and test recursivity support
                    ->openRow(5, ArticlesComment::class)
                    ->jsClick('[data-tab-model="articles_comments"] [data-tabs][data-model="articles_comments"]:not([default-tab])')
                    ->fillForm(ArticlesComment::class, ['name' => 'new recursive comment'], null, '[data-depth="2"]')
                    ->submitForm()->closeAlert()
                    ->assertSeeIn('[data-tabs][data-depth="0"][data-model="articles_comments"]', 'Comments (4)')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [5, 3, 2, 1], true, '[data-depth="1"]')
                    ->assertSeeIn('[data-tabs][data-depth="1"][data-model="articles_comments"]:not([default-tab])', 'Comments (1)')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [6], true, '[data-depth="2"]')

                    //Open second row, and check relations data
                    ->openRow(2, Article::class)
                    ->waitForElement('[data-tabs][data-model="articles_comments"]:contains("Comments (1)"):visible')
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (1)')
                    ->click('[data-tabs][data-model="articles_comments"]')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [4])
                    ->fillForm(ArticlesComment::class, ['name' => 'my second new related comment'])
                    ->submitForm()->closeAlert()

                    //Assert if another relation has no related data
                    ->openRow(3, Article::class)
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (0)');

            //Check if all data are correct
            $this->assertEquals([
                ['id' => 7, 'article_id' => 2, 'articles_comment_id' => null, 'name' => 'my second new related comment'],
                ['id' => 6, 'article_id' => null, 'articles_comment_id' => 5, 'name' => 'new recursive comment'],
                ['id' => 5, 'article_id' => 1, 'articles_comment_id' => null, 'name' => 'new related comment'],
                ['id' => 4, 'article_id' => 2, 'articles_comment_id' => null, 'name' => 'comment 1 of article 2'],
                ['id' => 3, 'article_id' => 1, 'articles_comment_id' => null, 'name' => 'comment 3'],
                ['id' => 2, 'article_id' => 1, 'articles_comment_id' => null, 'name' => 'comment 2'],
                ['id' => 1, 'article_id' => 1, 'articles_comment_id' => null, 'name' => 'comment 1'],
            ], ArticlesComment::select(['id', 'article_id', 'articles_comment_id', 'name'])->get()->toArray());
        });
    }

    public function test_add_first_child_relations_and_then_parent()
    {
        $articleRow = ['name' => 'my test article', 'score' => 5];

        $this->browse(function (DuskBrowser $browser) use ($articleRow) {
            $browser->openModelPage(Article::class)
                    //Click on related model and check given data
                    ->click('[data-tabs][data-model="articles_comments"] a')

                    //Add 2 related comments
                    ->fillForm(ArticlesComment::class, ['name' => 'new related comment'])->submitForm()->closeAlert()
                    ->fillForm(ArticlesComment::class, ['name' => 'new related comment 1'])->submitForm()->closeAlert()

                    //Check if new row has been given into table
                    ->waitForElement('[data-tabs][data-model="articles_comments"]:contains("Comments (2)"):visible')
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (2)')
                    // ->assertColumnRowData(ArticlesComment::class, 'id', [2, 1])
                    //Open default tab
                    ->jsClick('[data-tabs][data-model="articles"] a:contains('.trans('admin::admin.general-tab').')')

                    //Open created related row, and test recursivity support
                    ->fillForm(Article::class, $articleRow)
                    ->submitForm(Article::class)->closeAlert()

                    //Assert if another relation has no related data
                    ->openRow(1, Article::class)
                    ->waitForElement('[data-tabs][data-model="articles_comments"]:contains("Comments (2)"):visible')
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (2)');

            //Check if all data are correct
            $this->assertEquals([
                ['id' => 2, 'article_id' => 1, 'articles_comment_id' => null, 'name' => 'new related comment 1'],
                ['id' => 1, 'article_id' => 1, 'articles_comment_id' => null, 'name' => 'new related comment'],
            ], ArticlesComment::select(['id', 'article_id', 'articles_comment_id', 'name'])->get()->toArray());
        });
    }
}
