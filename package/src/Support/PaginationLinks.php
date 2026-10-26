<?php

namespace Esoboy\Ui\Support;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\UrlWindow;

/**
 * The links of a Laravel paginator as Pagination parts: previous, page numbers with
 * ellipses (length-aware paginators only), next.
 *
 * Inside a Livewire component using WithPagination the links call its page methods
 * with wire:click; anywhere else they are plain hrefs.
 */
final class PaginationLinks
{
    /**
     * @return list<array{type: 'previous'|'page'|'ellipsis'|'next', page?: int, active?: bool, disabled?: bool, attributes?: array<string, string>}>
     */
    public static function for(Paginator|CursorPaginator $paginator): array
    {
        $links = [self::previous($paginator)];

        if ($paginator instanceof LengthAwarePaginator) {
            foreach (self::elements($paginator) as $element) {
                if ($element === '...') {
                    $links[] = ['type' => 'ellipsis'];

                    continue;
                }

                foreach ($element as $page => $url) {
                    $links[] = [
                        'type' => 'page',
                        'page' => $page,
                        'active' => $page === $paginator->currentPage(),
                        'attributes' => self::action($paginator, $page, $url),
                    ];
                }
            }
        }

        $links[] = self::next($paginator);

        return $links;
    }

    private static function previous(Paginator|CursorPaginator $paginator): array
    {
        if ($paginator->onFirstPage()) {
            return ['type' => 'previous', 'disabled' => true, 'attributes' => []];
        }

        $target = $paginator instanceof CursorPaginator
            ? $paginator->previousCursor()?->encode()
            : $paginator->currentPage() - 1;

        return ['type' => 'previous', 'disabled' => false, 'attributes' => self::action($paginator, $target, $paginator->previousPageUrl())];
    }

    private static function next(Paginator|CursorPaginator $paginator): array
    {
        if (! $paginator->hasMorePages()) {
            return ['type' => 'next', 'disabled' => true, 'attributes' => []];
        }

        $target = $paginator instanceof CursorPaginator
            ? $paginator->nextCursor()?->encode()
            : $paginator->currentPage() + 1;

        return ['type' => 'next', 'disabled' => false, 'attributes' => self::action($paginator, $target, $paginator->nextPageUrl())];
    }

    /**
     * Laravel's own window, as its paginator views see it.
     *
     * @return list<array<int, string>|'...'>
     */
    private static function elements(LengthAwarePaginator $paginator): array
    {
        $window = UrlWindow::make($paginator);

        return array_values(array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]));
    }

    /**
     * @return array<string, string>
     */
    private static function action(Paginator|CursorPaginator $paginator, int|string|null $target, ?string $url): array
    {
        $component = app('view')->shared('__livewire');

        if ($component === null || ! method_exists($component, 'setPage')) {
            return ['href' => (string) $url];
        }

        $name = $paginator instanceof CursorPaginator ? $paginator->getCursorName() : $paginator->getPageName();

        return ['wire:click' => sprintf("setPage(%s, '%s')", is_int($target) ? $target : "'{$target}'", $name)];
    }
}
