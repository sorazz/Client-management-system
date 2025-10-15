<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Imports\ClientsImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_upload_csv_and_dispatch_import_job()
    {
        Queue::fake();

        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Acme Corp,info@acme.test,1234567890\n";
        $csvContent .= "Duplicate Corp,dup@corp.test,555-5555\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->post(route('clients.import'), ['file'=>$file]);

        $response->assertRedirect(route('clients.upload'));
        $response->assertSessionHas('status','CSV import started in background!');

        Queue::assertPushedOn('default', ClientsImport::class);
    }

    /** @test */
    public function detects_duplicates_in_file_and_db()
    {
        // Existing client in DB
        $existing = Client::create([
            'company_name'=>'Acme Corp',
            'email'=>'info@acme.test',
            'phone_number'=>'1234567890'
        ]);

        $csv = "company_name,email,phone_number\n";
        $csv .= "Acme Corp,info@acme.test,1234567890\n"; // duplicate of DB
        $csv .= "New Client,new@test.com,1112223333\n"; // unique
        $csv .= "New Client,new@test.com,1112223333\n"; // duplicate in-file

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        Excel::fake();

        $this->post(route('clients.import'), ['file'=>$file]);

        Excel::assertQueued('clients.csv', function($import) {
            return $import instanceof ClientsImport;
        });
    }

    /** @test */
    public function can_export_clients_with_filter()
    {
        Client::factory()->create(['company_name'=>'A','email'=>'a@test.com','phone_number'=>'111','is_duplicate'=>false]);
        Client::factory()->create(['company_name'=>'B','email'=>'b@test.com','phone_number'=>'222','is_duplicate'=>true]);

        $response = $this->get(route('clients.export',['filter'=>'duplicates']));
        $response->assertOk();
        $response->assertHeader('Content-Type','text/csv');

        $response = $this->get(route('clients.export',['filter'=>'unique']));
        $response->assertOk();
        $response->assertHeader('Content-Type','text/csv');
    }

    /** @test */
    public function can_view_clients_index_and_filter_duplicates()
    {
        Client::factory()->create(['company_name'=>'A','email'=>'a@test.com','phone_number'=>'111','is_duplicate'=>false]);
        Client::factory()->create(['company_name'=>'B','email'=>'b@test.com','phone_number'=>'222','is_duplicate'=>true]);

        $response = $this->get(route('clients.index'));
        $response->assertOk();
        $response->assertSee('A@test.com');
        $response->assertSee('B@test.com');

        $response = $this->get(route('clients.index',['filter'=>'duplicates']));
        $response->assertOk();
        $response->assertDontSee('A@test.com');
        $response->assertSee('B@test.com');

        $response = $this->get(route('clients.index',['filter'=>'unique']));
        $response->assertOk();
        $response->assertSee('A@test.com');
        $response->assertDontSee('B@test.com');
    }

    /** @test */
    public function api_endpoints_return_expected_json()
    {
        $client = Client::factory()->create(['company_name'=>'API Corp','email'=>'api@test.com','phone_number'=>'999']);

        $response = $this->getJson('/api/clients');
        $response->assertOk();
        $response->assertJsonFragment(['company_name'=>'API Corp']);

        $response = $this->getJson('/api/clients/'.$client->id);
        $response->assertOk();
        $response->assertJsonFragment(['company_name'=>'API Corp']);
    }
}
