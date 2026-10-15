<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'TechVault Admin',
            'email' => 'admin@liceo.edu.ph',
            'password' => 'password123',
            'role' => 'admin',
            'department' => 'ICT Office',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Liceo Student',
            'email' => 'user@liceo.edu.ph',
            'password' => 'password123',
            'role' => 'user',
            'department' => 'Computer Science',
            'status' => 'active',
        ]);

        $categories = collect([
            ['name' => 'Laptops', 'description' => 'Portable workstations for student labs and faculty.'],
            ['name' => 'Desktops', 'description' => 'Stationary workstations for offices and labs.'],
            ['name' => 'Networking', 'description' => 'Routers, switches, and access points.'],
            ['name' => 'Peripherals', 'description' => 'Monitors, keyboards, mice, and printers.'],
            ['name' => 'Audio-Visual', 'description' => 'Projectors and presentation equipment.'],
        ])->map(fn (array $category) => Category::create($category));

        $items = [
            ['asset_tag' => 'LDCU-LAP-001', 'name' => 'Dell Latitude 5420 Laptop', 'category_id' => $categories[0]->id, 'serial_number' => 'DL5420-001', 'status' => 'available', 'location' => 'ICT Lab 1'],
            ['asset_tag' => 'LDCU-LAP-002', 'name' => 'Lenovo ThinkPad E14', 'category_id' => $categories[0]->id, 'serial_number' => 'LTPE14-002', 'status' => 'assigned', 'location' => 'Faculty Room'],
            ['asset_tag' => 'LDCU-LAP-003', 'name' => 'HP EliteBook 840 G9', 'category_id' => $categories[0]->id, 'serial_number' => 'HP840G9-003', 'status' => 'under_repair', 'location' => 'Maintenance Shop'],
            ['asset_tag' => 'LDCU-DTP-001', 'name' => 'Dell OptiPlex 7010 Desktop', 'category_id' => $categories[1]->id, 'serial_number' => 'DO7010-004', 'status' => 'available', 'location' => 'ICT Lab 2'],
            ['asset_tag' => 'LDCU-DTP-002', 'name' => 'HP ProDesk 400 G7', 'category_id' => $categories[1]->id, 'serial_number' => 'HPPD400-005', 'status' => 'assigned', 'location' => 'Registrar Office'],
            ['asset_tag' => 'LDCU-NET-001', 'name' => 'Cisco Catalyst 2960 Switch', 'category_id' => $categories[2]->id, 'serial_number' => 'CC2960-006', 'status' => 'available', 'location' => 'Network Closet A'],
            ['asset_tag' => 'LDCU-NET-002', 'name' => 'Ubiquiti UniFi AP-AC-Lite', 'category_id' => $categories[2]->id, 'serial_number' => 'UBIAP-007', 'status' => 'assigned', 'location' => 'Library'],
            ['asset_tag' => 'LDCU-PER-001', 'name' => 'LG 24" LED Monitor', 'category_id' => $categories[3]->id, 'serial_number' => 'LG24-008', 'status' => 'available', 'location' => 'ICT Lab 1'],
            ['asset_tag' => 'LDCU-PER-002', 'name' => 'Epson EcoTank ET-2850 Printer', 'category_id' => $categories[3]->id, 'serial_number' => 'EPET2850-009', 'status' => 'disposed', 'location' => 'Storage'],
            ['asset_tag' => 'LDCU-AV-001', 'name' => 'Epson EB-X41 Projector', 'category_id' => $categories[4]->id, 'serial_number' => 'EPEBX41-010', 'status' => 'available', 'location' => 'AV Room'],
            ['asset_tag' => 'LDCU-AV-002', 'name' => 'Logitech C920 HD Webcam', 'category_id' => $categories[4]->id, 'serial_number' => 'LGC920-011', 'status' => 'under_repair', 'location' => 'Maintenance Shop'],
            ['asset_tag' => 'LDCU-LAP-004', 'name' => 'ASUS VivoBook 15', 'category_id' => $categories[0]->id, 'serial_number' => 'ASVB15-012', 'status' => 'available', 'location' => 'ICT Lab 3'],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
