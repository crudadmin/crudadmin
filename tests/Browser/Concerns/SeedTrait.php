<?php

namespace Gogol\Admin\Tests\Browser\Concerns;

use Gogol\Admin\Tests\App\Models\Articles\Article;
use Gogol\Admin\Tests\App\Models\Articles\Tag;

trait SeedTrait
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

    private function createTagList()
    {
        $tags = [
            ['type' => 'moovie', 'article_id' => 1 ],
            ['type' => 'moovie', 'article_id' => 2 ],
            ['type' => 'moovie', 'article_id' => 2 ],
            ['type' => 'moovie', 'article_id' => 2 ],
            ['type' => 'blog', 'article_id' => 2 ],
            ['type' => 'blog', 'article_id' => 3 ],
            ['type' => 'blog', 'article_id' => 4 ],
            ['type' => 'article', 'article_id' => 5 ],
            ['type' => 'article', 'article_id' => 6 ],
            ['type' => 'article', 'article_id' => 7 ],
        ];

        foreach ($tags as $item)
        {
            Tag::create([
                'type' => $item['type'],
                'article_id' => $item['article_id'],
            ]);
        }
    }

    private function getFieldsRelationFormData()
    {
        return [
            'relation1_id' => [9 => 'hellboy'],
            'relation2_id' => [8 => 'my option barefoot 7'],
            'relation3_id' => [10 => 'my second option spider-man 18'],
            'relation_multiple1' => [ 5 => 'aquaman', 6 => 'star is born', 3 => 'shrek' ],
            'relation_multiple2' => [ 9 => 'my option hellboy 8', 5 => 'my option aquaman 4', 11 => 'my option superman 10' ],
            'relation_multiple3' => [ 12 => 'second option john wick 22' ],
        ];
    }
}