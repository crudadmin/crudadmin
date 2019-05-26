<?php

namespace Gogol\Admin\Tests\Browser\Traits;

use Gogol\Admin\Tests\App\Models\Articles\Article;

trait ArticleSeedTrait
{
    /*
     * Create articles list
     */
    private function createArticleMoviesList()
    {
        $movies = [
            date('Y-m-10 H:i:s') => 'titanic',
            date('Y-m-11 H:i:s') => 'avengers',
            date('Y-m-12 H:i:s') => 'shrek',
            date('Y-m-14 H:i:s') => 'captain marvel',
            date('Y-m-15 H:i:s') => 'aquaman',
            date('Y-m-16 H:i:s') => 'star is born',
            date('Y-m-18 H:i:s') => 'hastrman',
            date('Y-m-19 H:i:s') => 'barefoot',
            date('Y-m-20 H:i:s') => 'hellboy',
            date('Y-m-21 H:i:s') => 'spider-man',
            date('Y-m-22 H:i:s') => 'superman',
            date('Y-m-26 H:i:s') => 'john wick',
        ];

        //Create 10 articles with movie names
        $i = 0;
        foreach ($movies as $date => $movie)
        {
            factory(Article::class)->create([
                'name' => $movie,
                'score' => $i++,
                'content' => 'my-search-content',
                'created_at' => $date,
            ]);
        }
    }
}