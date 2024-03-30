<?php

namespace Zatara\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Response as InertiaResponse;
use Zatara\Enums\FlashStyle;

trait WithHelpers
{
    public function render(string|null $component = null, array $data): InertiaResponse
    {
        $component = $component ?: $this->action->inertiaComponent;

        if (class_exists(\Laravel\Jetstream\Jetstream::class)) {
            return \Laravel\Jetstream\Jetstream::inertia()->render($this->request, $component, $data);
        }

        return \Inertia\Inertia::render($component, $data);
    }

    public function flash(string $message, ?FlashStyle $style = FlashStyle::SUCCESS): void
    {
        if (! FlashStyle::in($style)) {
            $style = FlashStyle::INFORMATION;
        }

        session()->flash('flash.banner', $message);
        session()->flash('flash.bannerStyle', $style->value);
    }

    public function condition(Request $request): bool
    {
        return true;
    }

    protected function authorizeCondition(): self
    {
        Gate::allowIf(
            condition: $this->condition($this->request)
        );

        return $this;
    }

    public function rules(Request $request): array
    {
        return [];
    }

    protected function validateRules(): self
    {
        $validator = Validator::make(
            data: $this->request->all(),
            rules: $this->rules($this->request)
        );

        $validator->validate();

        return $this;
    }
}
