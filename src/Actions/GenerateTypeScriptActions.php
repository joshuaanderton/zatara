<?php

namespace Zatara\Actions;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use PHPUnit\Event\Runtime\PHP;
use Spatie\TranslationLoader\LanguageLine;
use Zatara\Support\Facades\Zatara;

class GenerateTypeScriptActions
{
    use AsAction;

    public function handle(): Collection
    {
        $actions = Zatara::getActions();

        $content = collect([
            "import Action, { ActionRoute } from '@zatara/Action'",
            "import { set } from 'lodash'",
            "let actionMetas = []",
            "let actions = {}",
            ''
        ]);

        $content = $content->concat(
            $actions
                ->map(fn ($action) => json_decode(json_encode($action)))
                ->map(fn (object $action) => ("
actionMetas.push({
  namespace: '".str($action->action->controller)->after(Zatara::actionNamespace())->replace('\\', '.')->toString()."',
  class: class extends Action {
    public route: ActionRoute = {
      uri: '{$action->uri}',
      methods: ['".implode(', ', $action->methods)."'],
      as: '{$action->action->as}',
    }
    constructor(params: {[key: string]: any}) {
      super(params)
    }
  }
})"
                ))
        );

        $content = $content->concat([
            '',
            'actionMetas.forEach(actionMeta => set(actions, actionMeta.namespace, actionMeta.class))',
            '',
            'export default actions'
        ]);

        File::put(zatara_path('resources/js/generated/actions.ts'), $content->join(PHP_EOL));

        return $actions;
    }
}
