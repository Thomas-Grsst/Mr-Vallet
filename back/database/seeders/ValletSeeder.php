<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Machine;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ValletSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'vallet-demo-2026';

    private const AGENCIES = ['Lyon Est', 'Villeurbanne', 'Grenoble', 'Saint-Etienne', 'Clermont-Ferrand', 'Annecy', 'Valence'];

    private const MACHINES = [
        ['NAC112', 'Nacelle 12 m', 'Lyon Est', '2026-07-10'],
        ['NAC140', 'Nacelle 12 m', 'Grenoble', '2026-08-20'],
        ['NAC118', 'Nacelle 12 m', 'Annecy', '2026-04-15'],
        ['NAC089', 'Nacelle 16 m', 'Lyon Est', '2026-03-05'],
        ['NAC201', 'Nacelle 20 m', 'Villeurbanne', '2026-09-02'],
        ['MINI07', 'Mini-pelle 1.8 t', 'Lyon Est', null],
        ['MINI12', 'Mini-pelle 1.8 t', 'Saint-Etienne', null],
        ['MINI15', 'Mini-pelle 3.5 t', 'Clermont-Ferrand', null],
        ['COMP21', 'Compacteur', 'Lyon Est', null],
        ['COMP30', 'Compacteur', 'Annecy', null],
        ['ECH40', 'Echafaudage 40 m2', 'Lyon Est', null],
        ['ECH41', 'Echafaudage 40 m2', 'Valence', null],
    ];

    private const WORKSHOP_PERIODS = [
        ['MINI07', '2026-10-01', '2026-10-20', 'verin casse'],
    ];

    private const RESERVATIONS = [
        ['NAC112', 'BTP Rhone', '2026-10-14', '2026-10-18', 'Lyon Est'],
        ['NAC112', 'Maconnerie Duclos', '2026-10-16', '2026-10-17', 'Villeurbanne'],
        ['NAC089', 'Facades Martin', '2026-10-20', '2026-10-31', 'Lyon Est'],
        ['COMP21', 'M. Pereira (particulier)', '2026-10-12', '2026-10-12', 'Lyon Est'],
        ['ECH40', 'Constructions Alpes', '2026-10-06', '2026-10-24', 'Lyon Est'],
        ['NAC140', 'BTP Rhone', '2026-10-19', '2026-10-23', 'Grenoble'],
        ['MINI12', 'Artisan Ferreira', '2026-10-13', '2026-10-14', 'Saint-Etienne'],
    ];

    public function run(): void
    {
        $agencies = collect(self::AGENCIES)
            ->mapWithKeys(fn (string $name) => [$name => Agency::query()->create(['name' => $name])]);

        $machines = collect(self::MACHINES)
            ->mapWithKeys(fn (array $row) => [$row[0] => Machine::query()->create([
                'ref' => $row[0],
                'type' => $row[1],
                'agency_id' => $agencies[$row[2]]->id,
                'last_vgp_at' => $row[3],
            ])]);

        foreach (self::WORKSHOP_PERIODS as [$ref, $from, $to, $reason]) {
            $machines[$ref]->workshopPeriods()->create(['starts_at' => $from, 'ends_at' => $to, 'reason' => $reason]);
        }

        $agencies->each(fn (Agency $agency) => User::query()->create([
            'name' => $agency->name,
            'email' => Str::slug($agency->name).'@vallet.test',
            'password' => self::DEMO_PASSWORD,
            'role' => UserRole::Agency,
            'agency_id' => $agency->id,
        ]));

        User::query()->create([
            'name' => 'Atelier',
            'email' => 'atelier@vallet.test',
            'password' => self::DEMO_PASSWORD,
            'role' => UserRole::Workshop,
        ]);

        User::query()->create([
            'name' => 'Julie Ferrand',
            'email' => 'julie.ferrand@vallet.test',
            'password' => self::DEMO_PASSWORD,
            'role' => UserRole::Sales,
        ]);

        foreach (self::RESERVATIONS as [$ref, $client, $from, $to, $enteredBy]) {
            Reservation::query()->create([
                'machine_id' => $machines[$ref]->id,
                'client' => $client,
                'starts_at' => $from,
                'ends_at' => $to,
                'entered_by_agency_id' => $agencies[$enteredBy]->id,
            ]);
        }
    }
}
