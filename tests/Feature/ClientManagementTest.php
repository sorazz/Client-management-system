<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Client;

class ClientManagementTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function can_view_clients_index_with_filters()
    {
        // Create clients
        Client::create([
            'company_name' => 'Acme Corp',
            'email' => 'acme@test.com',
            'phone_number' => '1234567890',
            'is_duplicate' => true
        ]);
        Client::create([
            'company_name' => 'Unique Corp',
            'email' => 'unique@test.com',
            'phone_number' => '9876543210',
            'is_duplicate' => false
        ]);

        // 2. View all clients (no filter)
        $response = $this->get(route('clients.index'));
        $response->assertStatus(200);
        $response->assertSee('Acme Corp');
        $response->assertSee('Unique Corp');

        // Filter duplicates (only duplicates should appear)
        $response = $this->get(route('clients.index', ['filter' => 'duplicates']));
        $response->assertStatus(200);
        $response->assertSee('Acme Corp');
        $response->assertDontSee('Unique Corp'); // Unique client should NOT appear

        // Filter unique (only unique clients should appear)
        $response = $this->get(route('clients.index', ['filter' => 'unique']));
        $response->assertStatus(200);
        $response->assertSee('Unique Corp');    // Unique client SHOULD appear
        $response->assertDontSee('Acme Corp'); // Duplicate client SHOULD NOT appear


    }

    /** @test */
    public function can_view_upload_page()
    {
        $response = $this->get(route('clients.upload'));
        $response->assertStatus(200);
        $response->assertSee('Upload'); // Assuming your Blade has "Upload" text
    }

    /** @test */
    public function can_upload_csv_and_dispatch_import_job()
    {
        Queue::fake();

        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Acme Corp,info@acme.test,1234567890\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->post(route('clients.import'), ['file' => $file]);

        $response->assertRedirect(route('clients.upload'));
        $response->assertSessionHas('status', 'CSV import started in background!');
    }

    /** @test */
    public function can_export_clients_csv()
    {
        Client::create([
            'company_name' => 'Acme Corp',
            'email' => 'acme@test.com',
            'phone_number' => '1234567890',
            'is_duplicate' => true
        ]);

        Excel::fake();

        // Export all
        $response = $this->get(route('clients.export'));
        $response->assertStatus(200);

        // Export filtered duplicates
        $response = $this->get(route('clients.export', ['filter' => 'duplicates']));
        $response->assertStatus(200);

        // Export filtered unique
        $response = $this->get(route('clients.export', ['filter' => 'unique']));
        $response->assertStatus(200);

        Excel::assertDownloaded('clients.csv');
    }
}
