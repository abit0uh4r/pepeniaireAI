<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ManagerProvisioner;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateManager extends Command
{
    protected $signature = 'manager:create';

    protected $description = 'Provision the first manager account interactively';

    public function handle(ManagerProvisioner $provisioner): int
    {
        $name = $this->ask('Manager name', config('manager.name'));
        $email = $this->ask('Manager email', config('manager.email'));
        $password = $this->secret('Manager password');
        $confirmation = $this->secret('Confirm manager password');

        if ($password !== $confirmation) {
            $this->error('The manager passwords do not match.');

            return self::FAILURE;
        }

        try {
            $manager = $provisioner->provision($name, $email, $password);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Manager provisioned: {$manager->email}");

        return self::SUCCESS;
    }
}
