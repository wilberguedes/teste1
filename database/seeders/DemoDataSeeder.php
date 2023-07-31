<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Activities\Models\ActivityType;
use Modules\Billable\Models\Product;
use Modules\Brands\Models\Brand;
use Modules\Calls\Models\CallOutcome;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\Source;
use Modules\Core\Environment;
use Modules\Core\Models\Country;
use Modules\Deals\Database\Seeders\LostReasonSeeder;
use Modules\Deals\Models\Deal;
use Modules\Deals\Models\Pipeline;
use Modules\Documents\Enums\DocumentViewType;
use Modules\Documents\Models\Document;
use Modules\Documents\Models\DocumentType;
use Modules\Users\Models\User;

class DemoDataSeeder extends Seeder
{
    /**
     * Demo data pipeline.
     */
    protected ?Pipeline $pipeline = null;

    /**
     * Demo data products.
     */
    protected array $products = ['SEO Optimization', 'Web Design', 'Consultant Services', 'MacBook Pro', 'Marketing Services'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        settings(['company_country_id' => $this->getCountry()->getKey()]);

        $this->call([LostReasonSeeder::class], true);

        $this->createUsers()->each(function ($user, $index) {
            // For activity log causer and created_by
            Auth::loginUsingId($user->id);

            Product::factory()->for($user, 'creator')->create(['name' => $this->products[$index]]);

            Company::factory(5)->for($user)->for($user, 'creator')
                ->hasPhones()
                ->has($this->makeContactsFactories($user))
                ->for(Source::inRandomOrder()->first())
                ->has($this->makeDealFactories($user))
                ->create()
                ->each(function ($company) use ($user) {
                    $this->seedCommonRelations($company, $user);

                    $company->deals->each(fn ($deal) => $this->seedCommonRelations($deal, $user));

                    $company->contacts->each(function ($contact) use ($user) {
                        $this->seedCommonRelations($contact, $user);

                        $contact->deals()->get()->each(fn ($deal) => $this->seedCommonRelations($deal, $user));
                    });
                });
        });

        $firstUser = User::find(1);

        $this->createSampleDocument($firstUser);
        $this->markRandomDealsAsLostOrWon();
        $this->setUserCommonLogin($firstUser);

        Environment::capture([
            '_server_ip' => '',
            '_prev_app_url' => null,
        ]);
    }

    /**
     * Create users for the demo.
     */
    protected function createUsers()
    {
        return User::factory(5)->create(
            ['super_admin' => collect([0, 1])->random()]
        );
    }

    /**
     * Make contacts factories for the given user.
     */
    protected function makeContactsFactories(User $user)
    {
        return Contact::factory()->for($user)->for($user, 'creator')
            ->hasPhones()
            ->has(Deal::factory()->for($this->getPipeline())->for($user)->for($user, 'creator'))
            ->for(Source::inRandomOrder()->first())
            ->count(collect([0, 1, 2])->random());
    }

    /**
     * Make deals factories for the given user.
     */
    protected function makeDealFactories(User $user)
    {
        return Deal::factory()->for($this->getPipeline())->for($user)->for($user, 'creator');
    }

    /**
     * Get the pipeline intended for the demo data.
     */
    protected function getPipeline(): Pipeline
    {
        return $this->pipeline ??= Pipeline::first();
    }

    /**
     * Add demo document with template.
     */
    protected function createSampleDocument(User $user): void
    {
        $document = Document::factory()->singable()->create([
            'content' => file_get_contents(module_path('documents', 'resources/templates/proposals/branding-proposal.html')),
            'view_type' => DocumentViewType::NAV_LEFT_FULL_WIDTH,
            'user_id' => $user->getKey(),
            'created_by' => $user->getKey(),
            'document_type_id' => DocumentType::where('flag', 'proposal')->first()->getKey(),
            'title' => 'Branding Proposal',
            'brand_id' => Brand::first()->getKey(),
        ]);

        $contact = Contact::first();
        $document->contacts()->attach(Contact::first()->getKey());
        $document->companies()->attach($contact->companies->first()->getKey());
    }

    /**
     * Set the first user common login details.
     */
    protected function setUserCommonLogin(User $user): void
    {
        $user->name = 'Admin';
        $user->email = 'admin@test.com';
        $user->password = bcrypt('123123');
        $user->remember_token = Str::random(10);
        $user->timezone = 'Europe/Berlin';
        $user->access_api = true;
        $user->super_admin = true;
        $user->save();
    }

    /**
     * Seed the resources common relations.
     */
    protected function seedCommonRelations($model, User $user): void
    {
        $model->changelog()->update(
            $this->changelogAttributes($user)
        );

        $model->notes()->save(\Modules\Notes\Models\Note::factory()->for($user)->make());

        $model->calls()->save(
            \Modules\Calls\Models\Call::factory()
                ->for(CallOutcome::inRandomOrder()->first(), 'outcome')
                ->for($user)
                ->make()
        );

        $activity = $model->activities()->save(
            \Modules\Activities\Models\Activity::factory()->for($user)
                ->for($user, 'creator')
                ->for(ActivityType::inRandomOrder()->first(), 'type')
                ->make(['note' => null])
        );

        //  Attempted to lazy load [guestable] on model [Modules\Activities\Models] but lazy loading is disabled.
        $activity->load('guests.guestable');

        $activity->addGuest($user);

        if ($model instanceof \Modules\Contacts\Models\Contact) {
            $activity->addGuest($model);
        } else {
            if ($contact = $model->contacts?->first()) {
                $activity->addGuest($contact);
            }
        }
    }

    /**
     * Get the country for the demo.
     */
    protected function getCountry(): Country
    {
        return Country::where('name', 'United States')->first();
    }

    /**
     * Changelog attributes to overwrite.
     */
    protected function changelogAttributes(User $user): array
    {
        return [
            'causer_id' => $user->id,
            'causer_type' => $user::class,
            'causer_name' => $user->name,
        ];
    }

    /**
     * Mark random deals as won and lost.
     */
    protected function markRandomDealsAsLostOrWon(): void
    {
        Deal::take(5)->latest()->inRandomOrder()->get()->each->markAsLost('Probable cause');
        Deal::take(5)->oldest()->inRandomOrder()->get()->each->markAsWon();
    }
}
