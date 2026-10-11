<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PageSettingsRequest;
use App\Services\Medias\MediaService;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages et paramètres du site (cahier §7) : un onglet par groupe de SiteSettings::GROUPS, chacun derrière sa
 * permission. Chaque modification est auditée (AppSetting).
 */
class PageSettingsController extends Controller
{
    public function edit(Request $request, SiteSettings $settings): View
    {
        $groups = collect(SiteSettings::GROUPS)->filter(fn ($group) => $request->user()->can($group['permission']));
        $active = $request->query('onglet');

        return view('pages-settings.edit', [
            'groups' => $groups,
            'active' => $groups->has($active) ? $active : (old('_group') ?: $groups->keys()->first()),
            'settings' => $settings,
        ]);
    }

    public function update(PageSettingsRequest $request, string $group, SiteSettings $settings, MediaService $medias): RedirectResponse
    {
        $values = $request->settings();
        foreach ($request->imageUploads() as $key => $file) {
            $values[$key] = (string) $medias->store($file, $request->user(), SiteSettings::GROUPS[$group]['fields'][$key][0], 'Site')->id;
        }
        $settings->save($values);

        return redirect()->route('pages.edit', ['onglet' => $group])
            ->with('status', 'Onglet « '.SiteSettings::GROUPS[$group]['label'].' » enregistré.');
    }
}
