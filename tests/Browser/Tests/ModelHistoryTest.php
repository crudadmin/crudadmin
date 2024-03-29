<?php

namespace Admin\Tests\Browser\Tests;

use Admin\Models\ModelsHistory;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropUploads;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\App\Models\History\History;

class ModelHistoryTest extends BrowserTestCase
{
    use DropDatabase,
        DropUploads;

    /** @test */
    public function test_history_support()
    {
        $row = $this->getFormData();

        $updatedRow = $this->getUpdatedRow();

        $this->browse(function (DuskBrowser $browser) use ($row, $updatedRow) {
            $browser->openModelPage(History::class)

                    //Check if form values has been successfully filled
                    ->openForm()
                    ->waitForCkeditor() //ckeditor pause
                    ->fillForm(History::class, $row, 'sk')
                    ->assertHasFormValues(History::class, $row, 'sk')

                    //Check if row has been successfully saved
                    ->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))
                    ->closeAlert();

            //Assert if default history row exists
            $this->assertRowExists(ModelsHistory::class, $this->getHistoryRow(1, $row, 'sk'));

            //Open row, change to en language and change some form values
            $browser->openRow(1)
                    ->changeRowLanguage('en')
                    ->fillForm(History::class, $updatedRow, 'en')
                    ->saveForm()

                    ->assertSeeSuccess(trans('admin::admin.success-save'))
                    ->closeAlert()
                    ->closeForm();

            //Check if updated row snapshot correctly exists
            $rowSnapshot = $this->getHistoryRow(2, $this->createLangArray($updatedRow, $row, ['en', 'sk']));
            $this->assertRowExists(ModelsHistory::class, $rowSnapshot, 2);

            //Open history switcher
            $browser->click('[data-id="1"] [data-button="history"]')
                    ->whenAvailable('.modal', function($modal) use($row, $updatedRow) {
                        $modal->assertSeeIn('[data-history-id="1"] td[data-changes-length]', count($row))
                              ->assertSeeIn('[data-history-id="2"] td[data-changes-length]', count($updatedRow));
                    });

            //Open first row values and check editor values, also check history button state
            $browser->click('[data-history-id="1"] button')->pause(1000)->scrollToElement()
                    ->assertHasFormValues(History::class, array_merge($row, [
                        'editor' => null,
                        'select' => null,
                        'decimal' => null,
                        'file' => null,
                        'date' => null,
                    ]), 'en')
                    ->assertHasClass('[data-id="1"] [data-button="history"]', 'enabled-history');

            //Open actual history row and check values
            $browser->closeForm()
                    ->click('[data-id="1"] [data-button="history"]')
                    ->whenAvailable('.modal', function($modal) use($row, $updatedRow) {
                        $modal->click('[data-history-id="2"] button');
                    })
                    ->waitUntilMissing('.modal')
                    ->assertHasFormValues(History::class, $updatedRow, 'en');

            //Also check colorized changes
            $browser->assertElementExists('[data-field="string"][data-history-changed]')
                    ->assertElementExists('[data-field="text"][data-history-changed]')
                    ->assertElementExists('[data-field="editor"][data-history-changed]')
                    ->assertElementExists('[data-field="decimal"][data-history-changed]')
                    ->assertElementExists('[data-field="time"][data-history-changed]')
                    ->assertElementDoesNotExists('[data-field="integer"][data-history-changed]')
                    ->assertElementDoesNotExists('[data-field="file"][data-history-changed]')
                    ->assertElementDoesNotExists('[data-field="date"][data-history-changed]')
                    ->assertElementDoesNotExists('[data-field="checkbox"][data-history-changed]')
                    ->assertElementDoesNotExists('[data-field="radio"][data-history-changed]')
                    ->assertElementDoesNotExists('[data-field="select"][data-history-changed]');
        });
    }

    private function getHistoryRow($id, $row, $lang = null)
    {
        $row = $this->buildDbData(History::class, $row, $lang);

        ksort($row);

        if ( isset($row['checkbox']) ) {
            $row['checkbox'] = $row['checkbox'] ? 1 : 0;
        }

        return [
            'id' => $id,
            'table' => (new History)->getTable(),
            'row_id' => 1,
            'user_id' => 1,
            'data' => json_encode($row),
        ];
    }

    public function getFormData($key = null)
    {
        $data = [
            'string' => 'This is my string example value',
            'text' => 'This is my text example value',
            'select' => 'option a',
            'integer' => '10',
            'decimal' => '11.50',
            'file' => 'image1.jpg',
            'date' => date('d.m.Y'),
            'time' => date('14:00'),
            'checkbox' => true,
            'radio' => 'b',
            'editor' => '<p>This is my editor <strong>example</strong> value</p>',
        ];

        return isset($key) ? $data[$key] : $data;
    }

    public function getUpdatedRow()
    {
        return [
            'string' => 'This is my updated string value',
            'decimal' => '5.20',
            'text' => 'This is my updated text value',
            'editor' => '<p>This is my updated locale editor value</p>',
            'time' => '15:00',
        ];
    }

    /*
     * Merge two simple arrays and set language keys into each value
     */
    private function createLangArray($row1, $row2, $langs = [])
    {
        $data = [];

        foreach ($row1 as $key => $value) {
            if ((new History)->hasFieldParam($key, 'locale', true)) {
                $data[$key][$langs[1]] = $row2[$key];
                $data[$key][$langs[0]] = $value;
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
