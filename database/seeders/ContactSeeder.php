<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\ContactRole;
use App\Models\Entity;
use Illuminate\Database\Seeder;

/**
 * Seeds demo contacts linked to seeded entities.
 * Safe to re-run — skips if an entity already has contacts.
 */
class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $techSolutions = Entity::where('name', 'Tech Solutions Lda')->first();
        $obras = Entity::where('name', 'Obras & Construção SA')->first();
        $materiais = Entity::where('name', 'Materiais & Ferramentas Lda')->first();
        $parceiros = Entity::where('name', 'Parceiros Integrados SA')->first();

        $ceoRole = ContactRole::where('name', 'CEO')->value('id');
        $managerRole = ContactRole::where('name', 'Manager')->value('id');
        $purchRole = ContactRole::firstOrCreate(['name' => 'Purchasing'])->id;
        $salesRole = ContactRole::firstOrCreate(['name' => 'Sales'])->id;

        $contacts = [
            [
                'entity' => $techSolutions,
                'first_name' => 'Ana',
                'last_name' => 'Rodrigues',
                'role_id' => $ceoRole,
                'mobile' => '+351 91 111 0001',
                'email' => 'a.rodrigues@techsolutions.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
            [
                'entity' => $techSolutions,
                'first_name' => 'Carlos',
                'last_name' => 'Ferreira',
                'role_id' => $salesRole,
                'mobile' => '+351 91 111 0002',
                'email' => 'c.ferreira@techsolutions.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
            [
                'entity' => $obras,
                'first_name' => 'João',
                'last_name' => 'Sousa',
                'role_id' => $managerRole,
                'phone' => '+351 22 000 0002',
                'mobile' => '+351 96 222 0001',
                'email' => 'j.sousa@obrasc.pt',
                'gdpr_consent' => false,
                'status' => 'active',
            ],
            [
                'entity' => $materiais,
                'first_name' => 'Mariana',
                'last_name' => 'Costa',
                'role_id' => $purchRole,
                'mobile' => '+351 93 333 0001',
                'email' => 'm.costa@materiais.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
            [
                'entity' => $parceiros,
                'first_name' => 'Ricardo',
                'last_name' => 'Mendes',
                'role_id' => $ceoRole,
                'phone' => '+351 21 500 0011',
                'mobile' => '+351 91 500 0011',
                'email' => 'r.mendes@parceiros.pt',
                'gdpr_consent' => true,
                'status' => 'active',
            ],
        ];

        $nextNumber = (int) (Contact::max('number') ?? 0) + 1;

        foreach ($contacts as $data) {
            $entity = $data['entity'];
            unset($data['entity']);

            if (! $entity) {
                continue;
            }

            // Skip if a contact with this email already exists for this entity
            $email = $data['email'];
            $alreadyExists = Contact::where('entity_id', $entity->id)
                ->get(['id', 'email'])
                ->contains(fn ($c) => $c->email === $email);

            if (! $alreadyExists) {
                Contact::create(array_merge($data, [
                    'entity_id' => $entity->id,
                    'number' => $nextNumber++,
                ]));
            }
        }

        $this->command->info('ContactSeeder: '.count($contacts).' contacts seeded.');
    }
}
