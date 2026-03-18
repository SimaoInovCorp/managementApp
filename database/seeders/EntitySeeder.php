<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Entity;
use Illuminate\Database\Seeder;

/**
 * Seeds demo entities (clients and suppliers) for development testing.
 * Safe to re-run — uses updateOrCreate on the NIF field.
 */
class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $pt = Country::where('code', 'PT')->value('id');
        $es = Country::where('code', 'ES')->value('id');
        $de = Country::where('code', 'DE')->value('id');

        $entities = [
            // Clients
            [
                'type' => 'client',
                'nif' => '507957547',
                'name' => 'Tech Solutions Lda',
                'address' => 'Rua da Inovação, 42',
                'postal_code' => '1000-001',
                'locality' => 'Lisboa',
                'country_id' => $pt,
                'phone' => '+351 21 000 0001',
                'mobile' => '+351 91 000 0001',
                'email' => 'geral@techsolutions.pt',
                'website' => 'https://techsolutions.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
            [
                'type' => 'client',
                'nif' => '234567890',
                'name' => 'Obras & Construção SA',
                'address' => 'Av. das Empresas, 100',
                'postal_code' => '4050-001',
                'locality' => 'Porto',
                'country_id' => $pt,
                'phone' => '+351 22 000 0001',
                'mobile' => '+351 96 000 0001',
                'email' => 'info@obrasc.pt',
                'gdpr_consent' => false,
                'status' => 'active',
            ],
            [
                'type' => 'client',
                'nif' => 'ES12345678A',
                'name' => 'Distribuciones Ibéricas SL',
                'address' => 'Calle Mayor, 15',
                'postal_code' => '28013',
                'locality' => 'Madrid',
                'country_id' => $es,
                'mobile' => '+34 600 100 200',
                'email' => 'contact@distrib-ibericas.es',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
            // Suppliers
            [
                'type' => 'supplier',
                'nif' => '509876543',
                'name' => 'Materiais & Ferramentas Lda',
                'address' => 'Zona Industrial, Lote 5',
                'postal_code' => '3800-001',
                'locality' => 'Aveiro',
                'country_id' => $pt,
                'phone' => '+351 23 400 0001',
                'mobile' => '+351 93 000 0002',
                'email' => 'compras@materiais.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
            [
                'type' => 'supplier',
                'nif' => 'DE123456789',
                'name' => 'Euro Parts GmbH',
                'address' => 'Industriestrasse 77',
                'postal_code' => '80331',
                'locality' => 'München',
                'country_id' => $de,
                'phone' => '+49 89 100 2000',
                'email' => 'orders@europarts.de',
                'website' => 'https://europarts.de',
                'gdpr_consent' => false,
                'status' => 'active',
            ],
            // Both client and supplier
            [
                'type' => 'both',
                'nif' => '510555123',
                'name' => 'Parceiros Integrados SA',
                'address' => 'Rua da Parceria, 8',
                'postal_code' => '2700-001',
                'locality' => 'Amadora',
                'country_id' => $pt,
                'phone' => '+351 21 500 0010',
                'mobile' => '+351 91 500 0010',
                'email' => 'geral@parceiros.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
        ];

        // Assign sequential numbers starting after any existing ones
        $nextNumber = (int) (Entity::max('number') ?? 0) + 1;

        foreach ($entities as $data) {
            $nif = $data['nif'];
            unset($data['nif']);

            // Check if an entity with this NIF already exists (decrypt all)
            $exists = Entity::get(['id', 'nif'])
                ->first(fn ($e) => $e->nif === $nif);

            if ($exists) {
                $exists->update($data);
            } else {
                Entity::create(array_merge($data, [
                    'nif' => $nif,
                    'number' => $nextNumber++,
                ]));
            }
        }

        $this->command->info('EntitySeeder: '.count($entities).' entities seeded.');
    }
}
