<?php

namespace Admin\Tests\Browser\Tests;

use Admin;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\App\Models\Articles\Tag;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\Concerns\SeedTrait;
use Admin\Tests\App\Models\Articles\Article;
use Admin\Tests\App\Models\Fields\FieldsType;
use Admin\Tests\App\Models\Fields\FieldsRelation;

class TableRowsTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    private function getColumnsList()
    {
        return [
            'id', 'string', 'text', 'select', 'integer', 'decimal', 'file', 'date',
            'datetime', 'time', 'checkbox', 'radio', 'custom',
        ];
    }

    /** @test */
    public function test_full_grid_size()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->assertHasClass('li[data-size="full"]', 'active');
        });
    }

    /** @test */
    public function test_with_childs_grid_size()
    {
        //Create 100 articles
        factory(Article::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)
                    ->assertHasClass('li[data-size="full"]', 'active');
        });
    }

    /** @test */
    public function test_medium_grid_size()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Tag::class)
                    ->assertHasClass('li[data-size="medium"]', 'active');
        });
    }

    /** @test */
    public function test_available_columns()
    {
        //Create 100 articles
        factory(FieldsType::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->assertVisibleColumnsList(FieldsType::class, $this->getColumnsList());
        });
    }

    /** @test */
    public function test_columns_visibility_changed()
    {
        //Create 100 articles
        factory(FieldsType::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->press(trans('admin::admin.rows-list'));

            $hide_columns = ['string', 'text', 'select', 'checkbox'];

            //Hide columns
            foreach ($hide_columns as $column) {
                $browser->click('*[fields-list] input[data-column="'.$column.'"]');
            }

            //Check if all columns except hidden are available
            $browser->assertVisibleColumnsList(FieldsType::class, array_diff($this->getColumnsList(), $hide_columns));
        });
    }

    /** @test */
    public function test_hidden_columns_visibility_changed_with_relations_values()
    {
        //Create article testing data for relation purposes
        $this->createArticleMoviesList();

        //Create testing rows with belongsToMany relations
        $row = $this->getFieldsRelationFormData();

        FieldsRelation::create($this->buildDbData(FieldsRelation::class, $row));
        $this->saveFieldRelationsValues(FieldsRelation::class, $row);

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->openModelPage(FieldsRelation::class)
                    ->press(trans('admin::admin.rows-list'));

            $showColumns = ['relation1_id', 'relation_multiple1', 'relation_multiple2', 'relation_multiple3'];

            //Hide/show columns from list
            foreach ($showColumns as $column) {
                $browser->click('*[fields-list] input[data-column="'.$column.'"]');
            }

            //Add visible columns into column list
            $visible = array_merge(['id', 'relation2_id', 'relation3_id'], $showColumns);
            asort($visible);

            //Check if all columns except hidden are available
            $browser->waitForText('second option john w...') //wait till last column value in relation_multiple3 will be loaded
                    ->assertVisibleColumnsList(FieldsRelation::class, $visible)
                    ->assertTableRowExists(FieldsRelation::class, $this->getHiddenColumnsRowData($row));
        });
    }

    //Parse data items into table row format
    private function getHiddenColumnsRowData($row)
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                $item = implode(', ', $item);
            }

            return is_string($item) ? $this->strLimit($item, 20) : $item;
        }, ['id' => 1] + $row);
    }

    /*
     * Drag and drop, does not work drag&drop as axcepted
     */
    // public function test_drag_and_drop()
    // {
    //     //Create articles
    //     factory(Article::class, 5)->create();

    //     $this->browse(function (DuskBrowser $browser) {
    //         $browser->openModelPage(Article::class)
    //                 ->pause(300)
    //                 ->drag('tr[data-id="3"]', 'tr[data-id="5"]');

    //         $browser->assertColumnRowData(Article::class, 'id', [3, 5, 4, 2, 1]);
    //     });
    // }
}
