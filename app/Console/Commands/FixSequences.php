<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix-sequences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix PostgreSQL sequences for nacionalidades, departamentos and municipios tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing PostgreSQL sequences...');

        try {
            // Fix nacionalidades sequence
            DB::statement("SELECT setval('nacionalidades_id_seq', (SELECT COALESCE(MAX(id), 1) FROM nacionalidades))");
            $this->info('✓ Fixed nacionalidades_id_seq');

            // Fix departamentos sequence
            DB::statement("SELECT setval('departamentos_id_seq', (SELECT COALESCE(MAX(id), 1) FROM departamentos))");
            $this->info('✓ Fixed departamentos_id_seq');

            // Fix municipios sequence
            DB::statement("SELECT setval('municipios_id_seq', (SELECT COALESCE(MAX(id), 1) FROM municipios))");
            $this->info('✓ Fixed municipios_id_seq');

            $this->info('');
            $this->info('All sequences have been fixed successfully!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error fixing sequences: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
