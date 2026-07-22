<?php

namespace App\Helpers;

use App\Models\MtSection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Gere os embeds.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class EmbedHelper
{
    /**
     * Obtém os provedores de embeds disponíveis.
     *
     * @return array - Os provedores disponíveis.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected static function getProviders(): array
    {
        return [
            'youtube_video' => [
                'regex' => '(?:youtube\\.com\\/(?:[^\\/\\n\\s]+\\/\\S+\\/|(?:v|e(?:mbed)?)\\/|.*[?&]v=)|youtu\\.be\\/|music\\.youtube\\.com\\/watch\\?v=)([a-zA-Z0-9_-]{11})',
                'render' => fn ($id) => self::buildLazyLoadHtml("https://www.youtube.com/embed/{$id}", "https://img.youtube.com/vi/{$id}/hqdefault.jpg"),
            ],
            'youtube_playlist' => [
                'regex' => '(?:youtube\\.com|music\\.youtube\\.com)\\/playlist\\?list=([a-zA-Z0-9_-]+)',
                'render' => fn ($id) => self::buildLazyLoadHtml("https://www.youtube.com/embed/videoseries?list={$id}", self::getPlaylistThumbnail($id)),
            ],
        ];
    }

    /**
     * Obtém o HTML do embed.
     *
     * @param  MtSection  $section  - O objeto da secção.
     * @return string - O HTML do embed.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public static function getEmbed(MtSection $section): string
    {
        if (empty($section->link)) {
            return '';
        }

        $url = trim($section->link);
        $embedHtml = '';

        if (! empty($section->embed_type) && $section->embed_type !== 'link') {
            $providers = self::getProviders();
            $provider = $providers[$section->embed_type] ?? null;

            if ($provider) {
                preg_match('#'.$provider['regex'].'#', $url, $matches);
                $id = $matches[1] ?? null;
                if ($id) {
                    $embedHtml = $provider['render']($id);
                }
            }
        }
        $linkButton = "<div class='mt-2'><a href='".e($url)."' target='_blank' class='btn btn-sm btn-secondary'>Abrir Link Externo</a></div>";

        return $embedHtml.$linkButton;
    }

    /**
     * Obtém as definições (tipo e regex) dos provedores para serem usadas pelo JavaScript.
     *
     * @return string - As definições dos provedores.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public static function getJsDefinitions(): string
    {
        $definitions = collect(self::getProviders())->map(function ($provider, $key) {
            return [
                'type' => $key,
                'regex' => $provider['regex'],
                'label' => str_replace('_', ' ', Str::title($key)),
            ];
        })->values();

        return $definitions->toJson();
    }

    /**
     * Obtém o HTML do iframe.
     *
     * @param  string  $src  - A URL do iframe.
     * @return string - O HTML do iframe.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    private static function buildIframe(string $src): string
    {
        return "<div class='ratio ratio-16x9'><iframe src='{$src}' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe></div>";
    }

    private static function getPlaylistThumbnail(string $playlistId): string
    {
        $apiKey = config('services.youtube.api_key');
        $fallbackThumbnail = 'https://i.ytimg.com/vi/default/hqdefault.jpg';

        if (! $apiKey) {
            Log::warning('Chave de API do YouTube não configurada. A usar thumbnail de fallback para playlists.');

            return $fallbackThumbnail;
        }

        return cache()->remember("playlist_thumbnail_{$playlistId}", now()->addDay(), function () use ($playlistId, $apiKey, $fallbackThumbnail) {
            try {
                $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId={$playlistId}&maxResults=1&key={$apiKey}";
                $response = Http::get($url);

                if ($response->successful() && ! empty($response->json('items'))) {
                    return $response->json('items.0.snippet.thumbnails.high.url', $fallbackThumbnail);
                }
            } catch (\Exception $e) {
                Log::error('Falha ao obter thumbnail da playlist do YouTube: '.$e->getMessage());
            }

            return $fallbackThumbnail;
        });
    }

    /**
     * Obtém o HTML do lazy load.
     *
     * @param  string  $videoBaseUrl  - A URL do video.
     * @param  string  $thumbnailUrl  - A URL da thumbnail.
     * @return string - O HTML do lazy load.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    private static function buildLazyLoadHtml(string $videoBaseUrl, string $thumbnailUrl): string
    {
        $videoUrlWithAutoplay = $videoBaseUrl.(str_contains($videoBaseUrl, '?') ? '&' : '?').'autoplay=1';

        return "
            <div class='video-lazy-load ratio ratio-16x9' data-video-url='{$videoUrlWithAutoplay}'>
                <div class='video-thumbnail' style='background-image: url({$thumbnailUrl});'>
                    <div class='play-button'></div>
                </div>
            </div>
        ";
    }
}
