<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Artisan;
use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\FieldsType;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Gogol\Admin\Tests\Traits\DropUploads;
use Illuminate\Foundation\Auth\User;

class ModelGroupsTest extends BrowserTestCase
{
    use DropDatabase;

    /** @test */
    public function tabs_and_groups_recursively_shows_fields()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields groups & tabs')
                    ->clickLink('Fields groups & tabs')

                    //Check if base level field is available
                    ->assertPresent('input[name="field1"]')

                    //Check if first group name is available and fields of this group
                    ->assertSee('my_group1')
                    ->assertPresent('input[name="field6"]')
                    ->assertPresent('input[name="field7"]')
                    ->assertPresent('input[name="field8"]')
                    ->assertPresent('input[name="field9"]')

                    //Check if tabs are available
                    ->assertSee('my tab 1')
                    ->assertSee('my tab 2')

                    //Check if fields from tab 1 are visible
                    ->assertPresent('input[name="field2"]')
                    ->assertPresent('input[name="field3"]')

                    //Check if fields from second tab are not visible
                    ->assertMissing('input[name="field4"]')
                    ->assertMissing('input[name="field5"]')

                    //Open second tab and test check if fields are available
                    ->clickLink('my tab 2')
                    ->assertPresent('input[name="field4"]')
                    ->assertPresent('input[name="field5"]')

                    //Check if tabs from first tab are not visible
                    ->assertMissing('input[name="field2"]')
                    ->assertMissing('input[name="field3"]')

                    //Check if first group name is available and fields of this group
                    ->assertSee('my_group2')
                    ->assertPresent('input[name="field26"]')
                    ->assertPresent('input[name="field27"]')
                    ->assertPresent('input[name="field28"]')

                    //Check if tabs are available
                    ->assertSee('my tab 3')
                    ->assertSee('my tab 4')

                    //Check if fields from tab 3 are available
                    ->assertPresent('input[name="field10"]')
                    ->assertPresent('input[name="field11"]')

                    //Check if fields from tab 4 are hidden
                    ->assertMissing('input[name="field12"]')
                    ->assertMissing('input[name="field13"]')
                    ->assertMissing('input[name="field14"]')
                    ->assertMissing('input[name="field15"]')
                    ->assertMissing('input[name="field16"]')
                    ->assertMissing('input[name="field17"]')
                    ->assertMissing('input[name="field18"]')
                    ->assertMissing('input[name="field19"]')
                    ->assertMissing('input[name="field20"]')
                    ->assertMissing('input[name="field21"]')
                    ->assertMissing('input[name="field22"]')
                    ->assertMissing('input[name="field23"]')
                    ->assertMissing('input[name="field24"]')
                    ->assertMissing('input[name="field25"]')

                    //Open tab 4 and test check if fields are available
                    ->clickLink('my tab 4')
                    ->assertPresent('input[name="field12"]')
                    ->assertPresent('input[name="field13"]')
                    ->assertPresent('input[name="field14"]')
                    ->assertPresent('input[name="field15"]')
                    ->assertPresent('input[name="field16"]')
                    ->assertPresent('input[name="field17"]')
                    ->assertPresent('input[name="field18"]')
                    ->assertPresent('input[name="field19"]')
                    ->assertPresent('input[name="field20"]')
                    ->assertPresent('input[name="field21"]')

                    //Check if tabs are available
                    ->assertSee('my tab 5')
                    ->assertSee('my tab 6')

                    //Check if fields from tab 5 are available
                    ->assertPresent('input[name="field22"]')
                    ->assertPresent('input[name="field23"]')

                    //Check if fields from tab 6 are not available
                    ->assertMissing('input[name="field24"]')
                    ->assertMissing('input[name="field25"]')

                    //Open tab 6 and check if fields are available
                    ->clickLink('my tab 6')
                    ->assertPresent('input[name="field24"]')
                    ->assertPresent('input[name="field25"]')

                    //Check if fields from tab 5 are hidden
                    ->assertMissing('input[name="field22"]')
                    ->assertMissing('input[name="field23"]');
        });
    }

    /** @test */
    public function can_see_errors_in_recursive_tabs_for_validation_error_form()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields groups & tabs')
                    ->clickLink('Fields groups & tabs')
                    ->submitForm()
                    ->waitForText(trans('validation.required'), 2)
                    ->assertHasAttribute('li:contains("my tab 1")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 2")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 3")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 4")', 'has-error');
        });
    }

    /** @test */
    public function are_tabs_errors_disabled_on_clicks()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields groups & tabs')
                    ->clickLink('Fields groups & tabs')
                    ->submitForm()
                    ->waitForText(trans('validation.required'), 2)

                    //On click into input, groups error should be disabled
                    ->click('input[name="field2"]')
                    ->assertHasNotAttribute('li:contains("my tab 1")', 'has-error')

                    //On click into tab, error should be disabled
                    ->clickLink('my tab 2')
                    ->assertHasNotAttribute('li:contains("my tab 2")', 'has-error')

                    //Check if other two groups still has active link after clicks
                    ->assertHasAttribute('li:contains("my tab 3")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 4")', 'has-error')

                    //Open tab 4 and check if other tabs from same group has still error
                    ->clickLink('my tab 4')
                    ->assertHasNotAttribute('li:contains("my tab 4")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 3")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 5")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 6")', 'has-error')

                    //Click on subtabs and check if parent tabs are still in error state
                    ->clickLink('my tab 5')
                    ->assertHasAttribute('li:contains("my tab 6")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 3")', 'has-error')

                    //Click on other sub tab and check if parent tab has error
                    ->clickLink('my tab 6')
                    ->assertHasAttribute('li:contains("my tab 3")', 'has-error');
        });
    }

    /** @test */
    public function are_parents_tabs_errors_disabled_on_child_tab_click()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->loginAs(User::first())
                    ->visit(admin_action('DashboardController@index'))
                    ->assertSeeLink('Fields groups & tabs')
                    ->clickLink('Fields groups & tabs')
                    ->clickLink('my tab 4')
                    ->submitForm()
                    ->waitForText(trans('validation.required'), 2)
                    ->assertHasAttribute('li:contains("my tab 4")', 'has-error')

                    //Checl all tabs states on click into child tab
                    ->clickLink('my tab 6')
                    ->assertHasNotAttribute('li:contains("my tab 6")', 'has-error')
                    ->assertHasNotAttribute('li:contains("my tab 4")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 5")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 3")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 1")', 'has-error')
                    ->assertHasAttribute('li:contains("my tab 2")', 'has-error');
        });
    }
}