<?php

declare(strict_types=1);

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\Contract\CategoryQueryServiceInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Infrastructure\Repository\PdoCategoryReadQuery;
use Maatify\Category\Infrastructure\Repository\PdoCategoryTranslationCommandRepository;
use Maatify\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\SharedCommon\Infrastructure\SystemClock;
require __DIR__ . '/vendor/autoload.php';

/** @return never */
function standalone_consumer_fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function standalone_consumer_require(bool $condition, string $message): void
{
    if (!$condition) {
        standalone_consumer_fail($message);
    }
}

/**
 * @return list<class-string>
 */
function standalone_consumer_public_classes(): array
{
    return [
        CreateCategoryCommand::class,
        CreateCategoryTranslationCommand::class,
        'Maatify\\Category\\Command\\MoveCategoryCommand',
        'Maatify\\Category\\Command\\RestoreCategoryCommand',
        'Maatify\\Category\\Command\\RestoreCategoryTranslationCommand',
        'Maatify\\Category\\Command\\SoftDeleteCategoryCommand',
        'Maatify\\Category\\Command\\SoftDeleteCategoryTranslationCommand',
        'Maatify\\Category\\Command\\UpdateCategoryDisplayOrderCommand',
        'Maatify\\Category\\Command\\UpdateCategoryStatusCommand',
        'Maatify\\Category\\Command\\UpdateCategoryTranslationCommand',
        'Maatify\\Category\\DTO\\CategoryCollectionDTO',
        CategoryDTO::class,
        'Maatify\\Category\\DTO\\CategoryIdDTO',
        'Maatify\\Category\\DTO\\CategoryTranslationCollectionDTO',
        'Maatify\\Category\\DTO\\CategoryTranslationDTO',
        'Maatify\\Category\\Exception\\CategoryCodeAlreadyExistsException',
        'Maatify\\Category\\Exception\\CategoryCycleException',
        'Maatify\\Category\\Exception\\CategoryHasNonDeletedChildrenException',
        'Maatify\\Category\\Exception\\CategoryInvalidArgumentException',
        'Maatify\\Category\\Exception\\CategoryNotFoundException',
        'Maatify\\Category\\Exception\\CategoryPersistenceException',
        'Maatify\\Category\\Exception\\CategoryTransactionException',
        'Maatify\\Category\\Exception\\CategoryTranslationAlreadyExistsException',
        'Maatify\\Category\\Exception\\CategoryTranslationNotFoundException',
        PdoCategoryCommandRepository::class,
        PdoCategoryQueryReader::class,
        PdoCategoryReadQuery::class,
        PdoCategoryTranslationCommandRepository::class,
        PdoCategoryTransaction::class,
        CategoryCommandService::class,
        CategoryQueryService::class,
    ];
}

/**
 * @return list<class-string>
 */
function standalone_consumer_public_interfaces(): array
{
    return [
        'Maatify\\Category\\Contract\\CategoryCommandRepositoryInterface',
        'Maatify\\Category\\Contract\\CategoryCommandServiceInterface',
        'Maatify\\Category\\Contract\\CategoryQueryReaderInterface',
        'Maatify\\Category\\Contract\\CategoryQueryServiceInterface',
        'Maatify\\Category\\Contract\\CategoryReadQueryInterface',
        'Maatify\\Category\\Contract\\CategoryTransactionInterface',
        'Maatify\\Category\\Contract\\CategoryTranslationCommandRepositoryInterface',
        'Maatify\\Category\\Exception\\CategoryExceptionInterface',
    ];
}

/**
 * @param class-string $type
 */
function standalone_consumer_assert_type_loaded(string $type, string $kind): void
{
    $loaded = match ($kind) {
        'class' => class_exists($type),
        'interface' => interface_exists($type),
        'enum' => enum_exists($type),
        default => false,
    };

    standalone_consumer_require($loaded, sprintf('Public %s did not autoload: %s', $kind, $type));
}

/**
 * @param class-string $type
 */
function standalone_consumer_assert_installed_source(string $type, string $packageSourceRoot): void
{
    if (enum_exists($type)) {
        $reflection = new \ReflectionEnum($type);
    } else {
        $reflection = new \ReflectionClass($type);
    }

    $file = $reflection->getFileName();
    standalone_consumer_require(
        is_string($file) && str_starts_with($file, $packageSourceRoot . DIRECTORY_SEPARATOR),
        sprintf('Public type was not loaded from the installed package source: %s', $type),
    );
}

function standalone_consumer_install_schema(\PDO $pdo, string $schemaPath): void
{
    $schema = file_get_contents($schemaPath);
    standalone_consumer_require(is_string($schema), 'Unable to read the installed Category schema.');

    $schema = preg_replace(
        [
            '/^[ \t]*--[^\r\n]*(?:\r\n|\n|$)/m',
            '/^[ \t]*DELIMITER[ \t]+\S+[ \t]*$/mi',
        ],
        '',
        $schema,
    );
    standalone_consumer_require(is_string($schema), 'Unable to normalize the installed Category schema.');

    $statements = preg_split(
        '/;\s*(?=CREATE\s+(?:TABLE|TRIGGER)\b)/i',
        str_replace('$$', ';', trim($schema)),
        -1,
        PREG_SPLIT_NO_EMPTY,
    );
    standalone_consumer_require(
        is_array($statements) && count($statements) === 4,
        'The installed Category schema must contain two tables and two triggers.',
    );

    foreach ($statements as $statement) {
        standalone_consumer_require(is_string($statement), 'The schema contains a non-string SQL statement.');
        $pdo->exec($statement);
    }
}

function standalone_consumer_drop_schema(\PDO $pdo): void
{
    foreach ([
        'DROP TRIGGER IF EXISTS `trg_maa_category_categories_parent_not_self_ai`',
        'DROP TRIGGER IF EXISTS `trg_maa_category_categories_parent_not_self_bu`',
        'DROP TABLE IF EXISTS `maa_category_category_translations`',
        'DROP TABLE IF EXISTS `maa_category_categories`',
    ] as $statement) {
        $pdo->exec($statement);
    }
}

/** @return list<string> */
function standalone_consumer_database_objects(\PDO $pdo, string $objectType): array
{
    $statement = $pdo->query(
        'SELECT ' . ($objectType === 'tables' ? 'TABLE_NAME' : 'TRIGGER_NAME') . ' '
        . 'FROM information_schema.' . ($objectType === 'tables' ? 'TABLES' : 'TRIGGERS') . ' '
        . 'WHERE ' . ($objectType === 'tables' ? 'TABLE_SCHEMA' : 'TRIGGER_SCHEMA') . ' = DATABASE() '
        . 'ORDER BY 1',
    );
    standalone_consumer_require($statement !== false, 'Unable to inspect standalone database objects.');

    $values = $statement->fetchAll(\PDO::FETCH_COLUMN);
    $objects = [];
    foreach ($values as $value) {
        standalone_consumer_require(is_string($value), 'Database metadata contains an invalid object name.');
        $objects[] = $value;
    }

    return $objects;
}

$packageSourceRoot = realpath(__DIR__ . '/vendor/maatify/category/src');
standalone_consumer_require(
    is_string($packageSourceRoot),
    'The installed Category source directory is missing from the clean consumer.',
);

foreach (standalone_consumer_public_classes() as $type) {
    standalone_consumer_assert_type_loaded($type, 'class');
    standalone_consumer_assert_installed_source($type, $packageSourceRoot);
}

foreach (standalone_consumer_public_interfaces() as $type) {
    standalone_consumer_assert_type_loaded($type, 'interface');
    standalone_consumer_assert_installed_source($type, $packageSourceRoot);
}

standalone_consumer_assert_type_loaded(CategoryStatusEnum::class, 'enum');
standalone_consumer_assert_installed_source(CategoryStatusEnum::class, $packageSourceRoot);

$dsn = getenv('CATEGORY_STANDALONE_DSN');
$username = getenv('CATEGORY_STANDALONE_DB_USER');
$password = getenv('CATEGORY_STANDALONE_DB_PASSWORD');
standalone_consumer_require(
    is_string($dsn) && $dsn !== '' && is_string($username) && is_string($password),
    'Standalone consumer database environment is incomplete.',
);

try {
    $pdo = new \PDO($dsn, $username, $password, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (\PDOException $exception) {
    standalone_consumer_fail('Standalone consumer could not connect to Real MySQL: ' . $exception->getMessage());
}

$schemaPath = __DIR__ . '/vendor/maatify/category/schema/category.sql';
standalone_consumer_require(is_file($schemaPath), 'The installed Category schema file is missing.');

try {
    standalone_consumer_drop_schema($pdo);
    standalone_consumer_install_schema($pdo, $schemaPath);
    standalone_consumer_require(
        standalone_consumer_database_objects($pdo, 'tables') === [
            'maa_category_categories',
            'maa_category_category_translations',
        ],
        'The standalone schema did not create exactly its two package-owned tables.',
    );
    standalone_consumer_require(
        standalone_consumer_database_objects($pdo, 'triggers') === [
            'trg_maa_category_categories_parent_not_self_ai',
            'trg_maa_category_categories_parent_not_self_bu',
        ],
        'The standalone schema did not create exactly its two package-owned triggers.',
    );

    $commandService = new CategoryCommandService(
        new PdoCategoryCommandRepository($pdo, new ScopedOrderingManager()),
        new PdoCategoryQueryReader($pdo),
        new PdoCategoryTranslationCommandRepository($pdo),
        new PdoCategoryTransaction($pdo),
        new SystemClock(new \DateTimeZone('UTC')),
    );
    $queryService = new CategoryQueryService(new PdoCategoryReadQuery($pdo));

    standalone_consumer_require(
        $commandService instanceof CategoryCommandServiceInterface,
        'CategoryCommandService does not implement its public contract.',
    );
    standalone_consumer_require(
        $queryService instanceof CategoryQueryServiceInterface,
        'CategoryQueryService does not implement its public contract.',
    );

    $categoryId = $commandService->create(new CreateCategoryCommand('standalone-consumer-category'));
    $translationId = $commandService->createTranslation(
        new CreateCategoryTranslationCommand($categoryId, 'en-US', 'Standalone Category', null),
    );
    standalone_consumer_require($categoryId > 0 && $translationId > 0, 'Standalone mutation returned invalid IDs.');

    $category = $queryService->getById($categoryId);
    standalone_consumer_require($category->id === $categoryId, 'Standalone query returned the wrong Category.');
    standalone_consumer_require($category->code === 'standalone-consumer-category', 'Standalone Category code mismatch.');
    standalone_consumer_require(
        $queryService->listTranslations($categoryId)->count() === 1,
        'Standalone query did not return the stored translation.',
    );
} finally {
    standalone_consumer_drop_schema($pdo);
}

fwrite(STDOUT, "Standalone external consumer verification passed.\n");
