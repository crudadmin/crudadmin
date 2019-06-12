<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\App\Models\Articles\Article;
use Gogol\Admin\Tests\App\Models\Articles\ArticlesComment;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;

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
            [ 'article_id' => 1, 'name' => 'comment 1' ],
            [ 'article_id' => 1, 'name' => 'comment 2' ],
            [ 'article_id' => 1, 'name' => 'comment 3' ],
            [ 'article_id' => 2, 'name' => 'comment 1 of article 2' ],
        ]);

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)

                    //Open row, and load related model data, also check if count of loaded data is correct
                    ->openRow(1)->pause(400)
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (3)')

                    //Click on related model and check given data
                    ->click('[data-tabs][data-model="articles_comments"]')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [3, 2, 1])->pause(300)

                    //Fill form and save new related row.
                    ->fillForm(ArticlesComment::class, [ 'name' => 'new related comment' ])
                    ->submitForm()->pause(100)->closeAlert()

                    //Check if new row has been given into table
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (4)')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [5, 3, 2, 1])

                    //Open second row, and check relations data
                    ->openRow(2, Article::class)->pause(400)
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (1)')
                    ->click('[data-tabs][data-model="articles_comments"]')
                    ->assertColumnRowData(ArticlesComment::class, 'id', [4])->pause(300)
                    ->fillForm(ArticlesComment::class, [ 'name' => 'my second new related comment' ])
                    ->submitForm()->pause(100)->closeAlert()->pause(100)

                    //Assert if another relation has no related data
                    ->openRow(3, Article::class)->pause(400)
                    ->assertSeeIn('[data-tabs][data-model="articles_comments"]', 'Comments (0)');

            //Check if all data are correct
            $this->assertEquals([
                [ "id" => 6, "article_id" => 2, "name" => "my second new related comment"],
                [ "id" => 5, "article_id" => 1, "name" => "new related comment"],
                [ "id" => 4, "article_id" => 2, "name" => "comment 1 of article 2"],
                [ "id" => 3, "article_id" => 1, "name" => "comment 3"],
                [ "id" => 2, "article_id" => 1, "name" => "comment 2"],
                [ "id" => 1, "article_id" => 1, "name" => "comment 1"]
            ], ArticlesComment::select(['id', 'article_id', 'name'])->get()->toArray());
        });
    }
}