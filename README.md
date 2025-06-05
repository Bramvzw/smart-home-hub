<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Spotify Integration

This application includes a comprehensive Spotify integration that allows you to control your music playback and manage your Spotify content. To use this feature, you need to:

1. Create a Spotify Developer account at [https://developer.spotify.com/dashboard/](https://developer.spotify.com/dashboard/)
2. Create a new application in the Spotify Developer Dashboard
3. Add `http://localhost:8000/spotify/callback` to the Redirect URIs in your Spotify application settings
4. Add the following environment variables to your `.env` file:

```
SPOTIFY_CLIENT_ID=your_client_id
SPOTIFY_CLIENT_SECRET=your_client_secret
SPOTIFY_REDIRECT_URI=http://localhost:8000/spotify/callback
```

5. Visit the Spotify page in the application to connect your Spotify account and start controlling your music

### Features

The Spotify integration includes the following features:

#### Playback Control
- **Play/Pause**: Control the playback of your current track
- **Next/Previous**: Skip to the next or previous track in your queue
- **Seek**: Jump to a specific position in the current track using the progress bar
- **Volume Control**: Adjust the volume of your Spotify playback

#### Track Information
- **Now Playing**: View details about the currently playing track, including title, artist, and album artwork
- **Progress Bar**: See the current playback position and track duration
- **Next Up**: Preview the next track that will play in your queue

#### Library Management
- **Like/Unlike Tracks**: Save or remove tracks from your Spotify library directly from the player
- **Playlist Access**: Browse and play your Spotify playlists
- **Shuffle Play**: Start playback of a playlist in shuffle mode

#### Real-time Updates
- The player automatically updates to reflect the current state of your Spotify playback
- Changes made in other Spotify clients will be reflected in the application

### Known Limitations

- **Volume Control**: Some Spotify devices do not support volume control through the API. If you encounter an error when trying to adjust the volume, you may need to control the volume directly on the device.
- **Active Device Required**: You must have an active Spotify session on at least one device for the controls to work properly.
- **Premium Account**: A Spotify Premium account is required to use playback control features.
