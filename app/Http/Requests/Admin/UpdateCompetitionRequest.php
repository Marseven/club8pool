<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:160'],
            'discipline'            => ['required', 'in:8-ball,10-ball,snooker,blackball'],
            'structure'             => ['required', 'in:knockout,pools_knockout,pools_only,round_robin'],
            'format'                => ['required', 'string', 'max:32'],
            'player_slots'          => ['required', 'integer', 'min:2', 'max:256'],
            'pool_count'            => ['nullable', 'integer', 'min:0', 'max:32'],
            'pool_size'             => ['nullable', 'integer', 'min:2', 'max:32'],
            'qualifiers_per_pool'   => ['nullable', 'integer', 'min:1', 'max:8'],
            'race_to'               => ['required', 'integer', 'min:1', 'max:21'],
            'pool_race_to'          => ['nullable', 'integer', 'min:1', 'max:21'],
            'knockout_race_to'      => ['nullable', 'integer', 'min:1', 'max:21'],
            'shot_clock'            => ['required', 'integer', 'min:5', 'max:120'],
            'alternate_break'       => ['boolean'],
            'allow_draw'            => ['boolean'],
            'enable_warnings'       => ['boolean'],
            'push_out'              => ['boolean'],
            'frame_pause'           => ['nullable', 'integer', 'min:0', 'max:300'],
            'tiebreak_race'         => ['nullable', 'integer', 'min:1', 'max:21'],
            'venue'                 => ['nullable', 'string', 'max:200'],
            'city'                  => ['nullable', 'string', 'max:100'],
            'entry_fee'             => ['nullable', 'integer', 'min:0'],
            'deposit'               => ['nullable', 'integer', 'min:0'],
            'prize_pool'            => ['nullable', 'integer', 'min:0'],
            'starts_on'             => ['nullable', 'date'],
            'ends_on'               => ['nullable', 'date', 'after_or_equal:starts_on'],
            'registration_closes_at' => ['nullable', 'date'],
            'status'                => ['nullable', 'in:draft,registration,in_progress,finished'],
        ];
    }
}
