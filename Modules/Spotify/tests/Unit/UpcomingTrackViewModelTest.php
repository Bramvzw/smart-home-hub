<?php

namespace Modules\Spotify\Tests\Unit;

use Modules\Spotify\View\ViewModels\UpcomingTrackViewModel;
use Tests\TestCase;

class UpcomingTrackViewModelTest extends TestCase
{
    public function test_it_resolves_track_album_artwork(): void
    {
        $view = new UpcomingTrackViewModel([
            'name' => 'Next Track',
            'artists' => [['name' => 'Artist']],
            'album' => [
                'images' => [
                    ['url' => 'https://i.scdn.co/image/album-large'],
                ],
            ],
        ]);

        $this->assertTrue($view->hasTrack);
        $this->assertSame('https://i.scdn.co/image/album-large', $view->trackImage);
        $this->assertSame('Next Track', $view->trackName);
        $this->assertSame('Artist', $view->artistNames);
    }

    public function test_it_resolves_episode_artwork(): void
    {
        $view = new UpcomingTrackViewModel([
            'name' => 'Next Episode',
            'images' => [
                ['url' => 'https://i.scdn.co/image/episode-large'],
            ],
            'show' => [
                'publisher' => 'Publisher',
            ],
        ]);

        $this->assertTrue($view->hasTrack);
        $this->assertSame('https://i.scdn.co/image/episode-large', $view->trackImage);
        $this->assertSame('Next Episode', $view->trackName);
        $this->assertSame('Publisher', $view->artistNames);
    }

    public function test_it_marks_missing_next_track_without_placeholder_image(): void
    {
        $view = new UpcomingTrackViewModel(null);

        $this->assertFalse($view->hasTrack);
        $this->assertSame('', $view->trackImage);
        $this->assertSame('Geen volgend nummer', $view->trackName);
    }
}
