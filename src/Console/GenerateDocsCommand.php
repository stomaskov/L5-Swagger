<?php

namespace L5Swagger\Console;

use L5Swagger\Generator;
use Illuminate\Console\Command;

class GenerateDocsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate docs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $packagesWithDocs = config('swagger');
        if($packagesWithDocs) {
            foreach($packagesWithDocs as $package => $conf) {
                if(!isset($conf['enableGenerateDocs']) ||
                    (isset($conf['enableGenerateDocs']) && $conf['enableGenerateDocs'])) {
                    $this->info('Regenerating docs for: ' . $package);
                    Generator::generateDocs($conf);
                }
            }
        }
    }
}
