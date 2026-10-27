<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * The session-backed "shopping cart" of published courses.
 *
 * Every page reads and writes the same `course-cart` session key through this
 * class, so the catalog, homepage, global drawer and header badge always agree
 * on the selection.
 */
class CourseCart
{
    public const SESSION_KEY = 'course-cart';

    /**
     * The raw selection, keyed by course id.
     *
     * @return array<int, bool>
     */
    public function all(): array
    {
        return array_filter((array) session(self::SESSION_KEY, []));
    }

    /**
     * Whether the given course is currently selected.
     */
    public function has(int $courseId): bool
    {
        return ! empty($this->all()[$courseId]);
    }

    /**
     * Add a course to the cart, or remove it when already selected.
     *
     * @return bool True when the course was added, false when removed.
     */
    public function toggle(Course $course): bool
    {
        $cart = $this->all();

        if (! empty($cart[$course->id])) {
            unset($cart[$course->id]);
            $this->store($cart);

            return false;
        }

        $cart[$course->id] = true;
        $this->store($cart);

        return true;
    }

    /**
     * Remove a single course from the cart.
     */
    public function remove(int $courseId): void
    {
        $cart = $this->all();

        if (! isset($cart[$courseId])) {
            return;
        }

        unset($cart[$courseId]);
        $this->store($cart);
    }

    /**
     * Empty the whole cart.
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * How many published courses are selected.
     */
    public function count(): int
    {
        $courseIds = array_keys($this->all());

        if ($courseIds === []) {
            return 0;
        }

        return Course::query()
            ->published()
            ->whereIn('id', $courseIds)
            ->count();
    }

    /**
     * The selected published courses, in the order they were added.
     *
     * @return Collection<int, Course>
     */
    public function items(): Collection
    {
        $courseIds = array_keys($this->all());

        if ($courseIds === []) {
            return collect();
        }

        $positions = array_flip($courseIds);

        return Course::query()
            ->published()
            ->with('instructor')
            ->whereIn('id', $courseIds)
            ->get()
            ->sortBy(fn (Course $course): int => $positions[$course->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * The published course ids in the cart, in selection order.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return $this->items()->modelKeys();
    }

    /**
     * Drop stale, unpublished or falsy selections from the session.
     *
     * @return array<int, bool>
     */
    public function prune(): array
    {
        $cart = $this->all();

        if ($cart === []) {
            $this->clear();

            return [];
        }

        $publishedIds = Course::query()
            ->published()
            ->whereIn('id', array_keys($cart))
            ->pluck('id')
            ->all();

        $cart = array_intersect_key($cart, array_flip($publishedIds));

        $this->store($cart);

        return $cart;
    }

    /**
     * Persist the selection, clearing the key when the cart becomes empty.
     *
     * @param  array<int, bool>  $cart
     */
    protected function store(array $cart): void
    {
        if ($cart === []) {
            $this->clear();

            return;
        }

        session([self::SESSION_KEY => $cart]);
    }
}
