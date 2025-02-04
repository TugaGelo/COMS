<?php

namespace App\Filament\Admin\Resources\ConcourseResource\Pages;

use App\Filament\Admin\Resources\ConcourseResource;
use App\Models\Space;
use App\Models\User;
use App\Models\ConcourseRate;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Notifications\Notification;

class ViewSpaceConcourses extends Page
{
    use InteractsWithRecord;
































    protected static string $resource = ConcourseResource::class;

    protected static string $view = 'filament.admin.resources.concourse-resource.pages.view-space-concourses';

    public $name;
    public $price;
    public $status = 'available';
    public $spaces;
    public $canCreateSpace = false;
    public $drawMode = false;
    public $spaceDimensions = null;
    public $sqm = 0;
    public $rate;
    public $editingSpace = null;
    public $editingSpaceName;
    public $editingSpaceSqm;
    public $editingSpacePrice;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->spaces = $this->record->spaces()->get();
        $this->canCreateSpace = $this->record->layout !== null;
        $this->rate = $this->record->concourseRate->price;
    }

    public function toggleDrawMode()
    {
        $this->drawMode = !$this->drawMode;
        $this->dispatch('drawModeToggled', $this->drawMode);
    }

    public function setSpaceDimensions($dimensions)
    {
        $this->spaceDimensions = $dimensions;
        $this->dispatch('open-create-space-modal');
    }

    public function createSpace()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|min:0',
        ]);

        if (!$this->spaceDimensions) {
            Notification::make()
                ->title('Error')
                ->body('Please draw the space on the layout before creating.')
                ->danger()
                ->send();
            return;
        }

        Space::create([
            'user_id' => null,
            'concourse_id' => $this->record->id,
            'name' => $this->name,
            'price' => $this->price,
            'status' => 'available',
            'sqm' => $this->sqm,
            'is_active' => true,
            'space_width' => $this->spaceDimensions['width'],
            'space_length' => $this->spaceDimensions['height'],
            'space_area' => $this->spaceDimensions['width'] * $this->spaceDimensions['height'],
            'space_dimension' => $this->spaceDimensions['width'] . 'x' . $this->spaceDimensions['height'],
            'space_coordinates_x' => $this->spaceDimensions['x'],
            'space_coordinates_y' => $this->spaceDimensions['y'],
            'space_coordinates_x2' => $this->spaceDimensions['x'] + $this->spaceDimensions['width'],
            'space_coordinates_y2' => $this->spaceDimensions['y'] + $this->spaceDimensions['height'],
        ]);

        $this->reset(['name', 'price', 'spaceDimensions']);
        $this->drawMode = false;

        Notification::make()
            ->title('Space Created')
            ->body('A new space has been created. Please refresh the page to see the new space.')
            ->success()
            ->send();

        $this->spaces = $this->record->spaces()->get();

        return redirect()->route('filament.admin.resources.concourses.view-spaces', ['record' => $this->record->id]);
    }

    public function reload()
    {
        $this->spaces = $this->record->spaces()->get();
        $this->canCreateSpace = $this->record->layout !== null;

        Notification::make()
            ->title('Page Reloaded')
            ->body('The page data has been refreshed.')
            ->success()
            ->send();

        $this->dispatch('reload-page');
    }

    public function updatedSqm()
    {
        $this->sqm = is_numeric($this->sqm) ? (float) number_format((float) $this->sqm, 2, '.', '') : 0;
        $this->computePrice();
    }

    public function updatedEditingSpaceSqm()
    {
        $this->editingSpaceSqm = is_numeric($this->editingSpaceSqm) ? (float) number_format((float) $this->editingSpaceSqm, 2, '.', '') : 0;
        $this->editingSpacePrice = number_format($this->editingSpaceSqm * $this->rate, 2, '.', '');
    }

    protected function computePrice()
    {
        $this->sqm = is_numeric($this->sqm) ? (float) number_format((float) $this->sqm, 2, '.', '') : 0;
        $this->price = number_format($this->sqm * $this->rate, 2, '.', '');
    }

    public function deleteSpace($spaceId)
    {
        $space = Space::find($spaceId);

        if ($space) {
            $space->delete();

            Notification::make()
                ->title('Space deleted successfully')
                ->success()
                ->send();

            $this->reload();
        } else {
            Notification::make()
                ->title('Space not found')
                ->danger()
                ->send();
        }
    }

    public function editSpace($spaceId)
    {
        $space = Space::find($spaceId);
        if ($space) {
            $this->editingSpace = $space;
            $this->editingSpaceName = $space->name;
            $this->editingSpaceSqm = $space->sqm;
            $this->editingSpacePrice = $space->price;
        }
    }

    public function updateSpace()
    {
        $this->validate([
            'editingSpaceName' => 'required|string|max:255',
            'editingSpaceSqm' => 'required|numeric|min:0',
            'editingSpacePrice' => 'required|numeric|min:0',
        ]);

        if ($this->editingSpace) {
            $this->editingSpace->update([
                'name' => $this->editingSpaceName,
                'sqm' => $this->editingSpaceSqm,
                'price' => $this->editingSpacePrice,
            ]);

            $this->editingSpace = null;
            $this->reload();

            Notification::make()
                ->title('Space Updated')
                ->success()
                ->send();
        }
    }
}
