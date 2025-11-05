use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerificationMethodsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('verification_methods')->insert([
            ['method' => 'QR + Facial Recognition'],
            ['method' => 'Fingerprint'],
        ]);
    }
}
