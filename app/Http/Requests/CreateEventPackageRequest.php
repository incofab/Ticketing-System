<?php

namespace App\Http\Requests;

use App\Models\EventPackage;
use App\Models\SeatSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventPackageRequest extends FormRequest
{
  private ?EventPackage $eventPackage = null;
  function prepareForValidation()
  {
    $this->eventPackage = $this->route()->eventPackage;
  }
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'seat_section_id' => [
        'sometimes',
        Rule::requiredIf(empty($this->eventPackage)),
        'exists:seat_sections,id'
      ],
      'price' => ['required', 'numeric'],
      'entry_gate' => ['nullable', 'string'],
      'notes' => ['nullable', 'string'],
      'capacity' => [
        'required',
        'integer',
        function ($attr, $value, $fail) {
          /** @var SeatSection $seatSection */
          $seatSection = SeatSection::query()->findOrFail(
            request('seat_section_id')
          );
          $allocatedCapacity = EventPackage::whereSeatSectionId(
            $seatSection->id
          )->sum('capacity');

          $availableCapacity =
            $seatSection->capacity -
            $allocatedCapacity -
            ($this->eventPackage?->capacity ?? 0);

          if ($availableCapacity < 1) {
            $fail('There are no available seats');
            return;
          }
          if ($availableCapacity < $value) {
            $fail("There are only $availableCapacity available seat capacity");
            return;
          }
        }
      ],
      'title' => [
        'required',
        'string',
        Rule::unique('event_packages', 'title')
          ->where('seat_section_id', $this->seat_section_id)
          ->when(
            $this->eventPackage,
            fn($q) => $q->ignore($this->eventPackage->id, 'id')
          )
      ]
    ];
  }
}
