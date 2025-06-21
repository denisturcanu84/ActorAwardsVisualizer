<?php

namespace ActorAwards\Services;

/**
 * Handles all interactions with The Movie Database (TMDB) API
 *
 * This service class manages API requests to TMDB for actor/movie data
 * and handles image URL construction.
 */
class TmdbService
{
    // Constants for building API request URLs
    private const API_KEY_PARAM = '?api_key='; // Required for all API calls
    private const QUERY_PARAM = '&query=';     // Used for search endpoints
    
    private string $apiKey;  // Stores the TMDB API key for authentication
    private string $baseUrl; // Base URL for TMDB API
    
    public function __construct(string $apiKey, string $baseUrl = 'https://api.themoviedb.org/3')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Search for an actor by name using TMDB's /search/person endpoint
     *
     * @param string $name Actor's name to search for
     * @return array|null First matching actor result or null if not found
     *
     * Example response contains: id, name, popularity, profile_path, known_for (movies)
     */
    public function searchActor(string $name): ?array
    {
        // Build URL: /search/person?api_key=XXX&query=encoded_name
        $url = $this->baseUrl . '/search/person' . self::API_KEY_PARAM . $this->apiKey . self::QUERY_PARAM . urlencode($name);
        $json = @file_get_contents($url); // @ suppresses warnings if request fails
        $data = $json ? json_decode($json, true) : [];
        return $data['results'][0] ?? null; // Return first result or null
    }
    
    /**
     * Gets detailed info about an actor using TMDB's /person/{id} endpoint
     *
     * @param int $tmdbId TMDB's unique actor ID
     * @return array Actor details or empty array if request fails
     *
     * Returns comprehensive info including biography, birthday, deathday,
     * place of birth, and filmography count.
     */
    public function getActorDetails(int $tmdbId): array
    {
        // Build URL: /person/{id}?api_key=XXX
        $url = $this->baseUrl . '/person/' . $tmdbId . self::API_KEY_PARAM . $this->apiKey;
        $json = @file_get_contents($url);
        return $json ? json_decode($json, true) : [];
    }
    
    /**
     * Constructs full profile image URL from TMDB's relative path
     *
     * @param string|null $profilePath Relative path from TMDB (e.g. "/abc123.jpg")
     * @return string|null Full image URL or null if no path provided
     * /w400 is the standard size for profile images
     */
    public function getProfileImageUrl(?string $profilePath): ?string
    {
        return $profilePath ? 'https://image.tmdb.org/t/p/w400' . $profilePath : null;
    }

    /**
     * Gets the base URL for poster images.
     *
     * @return string
     */
    public function getPosterBaseUrl(): string
    {
        return 'https://image.tmdb.org/t/p/w500';
    }

    /**
     * Constructs a full poster image URL from a relative path.
     *
     * @param string|null $posterPath
     * @return string|null
     */
    public function getPosterImageUrl(?string $posterPath): ?string
    {
        return $posterPath ? $this->getPosterBaseUrl() . $posterPath : null;
    }
    
    /**
     * Gets an actor's movie credits sorted by popularity
     *
     * @param int $tmdbId TMDB actor ID
     * @param int $limit Max number of movies to return (default 4)
     * @return array List of movies sorted by popularity
     *
     * Uses TMDB's /person/{id}/movie_credits endpoint which returns
     * all movies the actor appeared in. We then:
     * 1. Sort by popularity (highest first)
     * 2. Limit to $limit most popular movies
     * Each movie includes title, character played, and poster path
     */
    public function getActorMovies(int $tmdbId, int $limit = 4): array
    {
        $url = $this->baseUrl . '/person/' . $tmdbId . '/movie_credits' . self::API_KEY_PARAM . $this->apiKey;
        $json = @file_get_contents($url);
        $data = $json ? json_decode($json, true) : [];

        if (!isset($data['cast'])) {
            return [];
        }

        // Sort by popularity (TMDB's popularity score)
        usort($data['cast'], function($a, $b) {
            return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
        });
        
        return array_slice($data['cast'], 0, $limit);
    }
    
    /**
     * Searches for movies by title using TMDB's /search/movie endpoint
     *
     * @param string $title Movie title to search for
     * @param int|null $year Optional year to narrow down search
     * @return array|null First matching movie or null if not found
     *
     * Returns basic movie info including title, release date, overview,
     * and poster path. Useful for finding TMDB IDs when you only know
     * the movie title.
     */
    public function searchMovie(string $title, ?int $year = null): ?array
    {
        // Build URL: /search/movie?api_key=XXX&query=encoded_title
        $url = $this->baseUrl . '/search/movie' . self::API_KEY_PARAM . $this->apiKey . self::QUERY_PARAM . urlencode($title);
        if ($year) {
            $url .= '&primary_release_year=' . $year;
        }
        $json = @file_get_contents($url);
        $data = $json ? json_decode($json, true) : [];

        // If we have results, try to find an exact match before falling back to the first result.
        if (!empty($data['results'])) {
            foreach ($data['results'] as $result) {
                // Prioritize exact title matches (case-insensitive).
                if (isset($result['title']) && strtolower($result['title']) === strtolower($title)) {
                    return $result;
                }
            }
            // If no exact match is found, return the first result as a fallback.
            return $data['results'][0];
        }

        return null;
    }
    
    /**
     * Get movie details by TMDB ID
     */
    public function getMovieDetails(int $tmdbId): array
    {
        $url = $this->baseUrl . '/movie/' . $tmdbId . self::API_KEY_PARAM . $this->apiKey;
        $json = @file_get_contents($url);
        return $json ? json_decode($json, true) : [];
    }
    
    /**
     * Search for a TV show by title
     * 
     * @param string $title The title of the TV show.
     * @param int|null $year Optional year of the first air date.
     * @return array|null The first matching TV show or null.
     */
    public function searchTvShow(string $title, ?int $year = null): ?array
    {
        $url = $this->baseUrl . '/search/tv' . self::API_KEY_PARAM . $this->apiKey . self::QUERY_PARAM . urlencode($title);
        if ($year) {
            $url .= '&first_air_date_year=' . $year;
        }
        $json = @file_get_contents($url);
        $data = $json ? json_decode($json, true) : [];

        // If we have results, try to find an exact match before falling back to the first result.
        if (!empty($data['results'])) {
            foreach ($data['results'] as $result) {
                // For TV shows, the title key is 'name'.
                if (isset($result['name']) && strtolower($result['name']) === strtolower($title)) {
                    return $result;
                }
            }
            // If no exact match is found, return the first result as a fallback.
            return $data['results'][0];
        }

        return null;
    }
    
    /**
     * Get TV show details by TMDB ID
     */
    public function getTvShowDetails(int $tmdbId): array
    {
        $url = $this->baseUrl . '/tv/' . $tmdbId . self::API_KEY_PARAM . $this->apiKey;
        $json = @file_get_contents($url);
        return $json ? json_decode($json, true) : [];
    }

    /**
     * Searches for a production (movie or TV show) by title and optional year.
     *
     * It prioritizes searching with the year if provided, then falls back to searching
     * without the year. It checks for movies first, then TV shows.
     *
     * @param string $title The title of the production.
     * @param int|null $year The year of release or first air date.
     * @return array|null The first matching production result, or null if not found.
     */
    public function findProduction(string $title, ?int $year = null): ?array
    {
        // First, try to find a match with the year if it's provided.
        if ($year !== null) {
            $result = $this->searchMovie($title, $year) ?? $this->searchTvShow($title, $year);
            if ($result) {
                return $result;
            }
        }

        // As a fallback (or if no year was given), search without the year.
        return $this->searchMovie($title) ?? $this->searchTvShow($title);
    }

    public function getProductionDetails(int $tmdbId, string $productionType): array
    {
        if (strtolower($productionType) === 'movie') {
            return $this->getMovieDetails($tmdbId);
        } elseif (strtolower($productionType) === 'tv') {
            return $this->getTvShowDetails($tmdbId);
        }
        
        return []; // return empty array for unknown production types
    }
}
