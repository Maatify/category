<?php

declare(strict_types=1);

use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldScopeDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ContentField\Mutation\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\ImageRole\Lifecycle\Enum\CategoryImageRoleStatusEnum;
use Maatify\Category\Query\Infrastructure\PdoCategoryManagementReadQuery;
use Maatify\Category\Infrastructure\PdoCategoryCommandRepository;
use Maatify\Category\Query\Infrastructure\PdoCategoryQueryReader;
use Maatify\Category\Query\Infrastructure\PdoCategoryReadQuery;
use Maatify\Category\Content\Infrastructure\PdoCategoryContentCommandRepository;
use Maatify\Category\ImageAssignment\Infrastructure\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\ImageRole\Infrastructure\PdoCategoryImageRoleCommandRepository;
use Maatify\Category\ContentField\Infrastructure\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\Api\Service\CategoryCommandService;
use Maatify\Category\Api\Service\CategoryManagementQueryService;
use Maatify\Category\Api\Service\CategoryQueryService;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use Maatify\SharedCommon\Infrastructure\SystemClock;

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
 * Build the public type inventory from the installed package source itself.
 *
 * This intentionally does not maintain a second, hand-curated list in the
 * consumer: every PHP source file must expose a discoverable package type,
 * and every discovered class, interface, or enum must then autoload from the
 * installed package.
 *
 * @return list<array{kind: 'class'|'interface'|'enum', name: string}>
 */
function standalone_consumer_public_types(string $packageSourceRoot): array
{
    $types = [];
    $seen = [];
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($packageSourceRoot, \FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
            continue;
        }

        $source = file_get_contents($fileInfo->getPathname());
        if (!is_string($source)) {
            standalone_consumer_fail('Unable to read installed package source: ' . $fileInfo->getPathname());
        }

        $namespaceMatches = [];
        if (preg_match(
            '/^\s*namespace\s+([A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*)\s*;/m',
            $source,
            $namespaceMatches,
        ) !== 1) {
            standalone_consumer_fail('Package source file has no discoverable namespace: ' . $fileInfo->getPathname());
        }
        $namespace = $namespaceMatches[1];

        $fileTypeCount = 0;
        $tokens = token_get_all($source);
        $previousMeaningfulToken = null;
        $tokenCount = count($tokens);
        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];
            if (!is_array($token)) {
                if (trim($token) !== '') {
                    $previousMeaningfulToken = $token;
                }
                continue;
            }

            $tokenId = $token[0];
            if (in_array($tokenId, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if (in_array($tokenId, [T_CLASS, T_INTERFACE, T_ENUM], true)) {
                $nextIndex = $index + 1;
                while ($nextIndex < $tokenCount) {
                    $nextToken = $tokens[$nextIndex];
                    if (is_array($nextToken) && in_array($nextToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        $nextIndex++;
                        continue;
                    }
                    if (is_string($nextToken) && trim($nextToken) === '') {
                        $nextIndex++;
                        continue;
                    }
                    break;
                }

                $nextToken = $tokens[$nextIndex] ?? null;
                if (
                    is_array($nextToken)
                    && $nextToken[0] === T_STRING
                    && !($tokenId === T_CLASS && $previousMeaningfulToken === T_NEW)
                ) {
                    $name = $nextToken[1];

                    $type = $namespace . '\\' . $name;
                    if (isset($seen[$type])) {
                        standalone_consumer_fail('Package source declares a duplicate public type: ' . $type);
                    }
                    $seen[$type] = true;
                    $types[] = [
                        'kind' => match ($tokenId) {
                            T_CLASS => 'class',
                            T_INTERFACE => 'interface',
                            T_ENUM => 'enum',
                        },
                        'name' => $type,
                    ];
                    $fileTypeCount++;
                }
            }

            $previousMeaningfulToken = $tokenId;
        }

        standalone_consumer_require(
            $fileTypeCount === 1,
            'Package source file must declare exactly one public class, interface, or enum: ' . $fileInfo->getPathname(),
        );
    }

    standalone_consumer_require($types !== [], 'Installed package source contains no public types.');
    usort($types, static fn (array $left, array $right): int => strcmp($left['name'], $right['name']));

    return $types;
}

/**
 * @phpstan-assert class-string $type
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
    if (!is_string($schema)) {
        standalone_consumer_fail('Unable to read the installed Category schema.');
    }

    $schema = preg_replace(
        [
            '/^[ \t]*--[^\r\n]*(?:\r\n|\n|$)/m',
            '/^[ \t]*DELIMITER[ \t]+\S+[ \t]*$/mi',
        ],
        '',
        $schema,
    );
    if (!is_string($schema)) {
        standalone_consumer_fail('Unable to normalize the installed Category schema.');
    }

    $statements = preg_split(
        '/;\s*(?=CREATE\s+(?:TABLE|TRIGGER)\b)/i',
        str_replace('$$', ';', trim($schema)),
        -1,
        PREG_SPLIT_NO_EMPTY,
    );
    if (!is_array($statements) || count($statements) !== 7) {
        standalone_consumer_fail('The installed Category schema must contain five tables and two triggers.');
    }

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

function standalone_consumer_drop_schema(\PDO $pdo): void
{
    foreach ([
        'DROP TRIGGER IF EXISTS `trg_maa_category_categories_parent_not_self_ai`',
        'DROP TRIGGER IF EXISTS `trg_maa_category_categories_parent_not_self_bu`',
        'DROP TABLE IF EXISTS `maa_category_category_content_fields`',
        'DROP TABLE IF EXISTS `maa_category_category_image_assignments`',
        'DROP TABLE IF EXISTS `maa_category_category_image_roles`',
        'DROP TABLE IF EXISTS `maa_category_category_contents`',
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
        . 'ORDER BY BINARY ' . ($objectType === 'tables' ? 'TABLE_NAME' : 'TRIGGER_NAME'),
    );
    if ($statement === false) {
        standalone_consumer_fail('Unable to inspect standalone database objects.');
    }

    $values = $statement->fetchAll(\PDO::FETCH_COLUMN);
    $objects = [];
    foreach ($values as $value) {
        if (!is_string($value)) {
            standalone_consumer_fail('Database metadata contains an invalid object name.');
        }
        $objects[] = $value;
    }

    return $objects;
}

$packageSourceRoot = realpath(__DIR__ . '/vendor/maatify/category/src');
if (!is_string($packageSourceRoot)) {
    standalone_consumer_fail('The installed Category source directory is missing from the clean consumer.');
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoloadPath)) {
    standalone_consumer_fail('The clean consumer Composer autoload file is missing.');
}
require $autoloadPath;

$publicTypes = standalone_consumer_public_types($packageSourceRoot);
$publicTypeNames = [];
foreach ($publicTypes as $definition) {
    standalone_consumer_assert_type_loaded($definition['name'], $definition['kind']);
    standalone_consumer_assert_installed_source($definition['name'], $packageSourceRoot);
    $publicTypeNames[] = $definition['name'];
}
fwrite(STDOUT, sprintf("Installed package public inventory (%d): %s\n", count($publicTypes), implode(', ', $publicTypeNames)));

$dsn = getenv('CATEGORY_STANDALONE_DSN');
$username = getenv('CATEGORY_STANDALONE_DB_USER');
$password = getenv('CATEGORY_STANDALONE_DB_PASSWORD');
if (!is_string($dsn) || $dsn === '' || !is_string($username) || !is_string($password)) {
    standalone_consumer_fail('Standalone consumer database environment is incomplete.');
}

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
            'maa_category_category_content_fields',
            'maa_category_category_contents',
            'maa_category_category_image_assignments',
            'maa_category_category_image_roles',
        ],
        'The standalone schema did not create exactly its five package-owned tables.',
    );
    standalone_consumer_require(
        standalone_consumer_database_objects($pdo, 'triggers') === [
            'trg_maa_category_categories_parent_not_self_ai',
            'trg_maa_category_categories_parent_not_self_bu',
        ],
        'The standalone schema did not create exactly its two package-owned triggers.',
    );

    $clock = new SystemClock(new \DateTimeZone('Africa/Cairo'));
    $commandService = new CategoryCommandService(
        new PdoCategoryCommandRepository($pdo, new ScopedOrderingManager()),
        new PdoCategoryQueryReader($pdo, $clock),
        new PdoCategoryContentCommandRepository($pdo),
        new PdoCategoryImageAssignmentCommandRepository($pdo, new ScopedOrderingManager()),
        new PdoCategoryContentFieldCommandRepository($pdo, new ScopedOrderingManager()),
        new PdoTransactionRunner($pdo),
        $clock,
        new PdoCategoryImageRoleCommandRepository($pdo),
    );
    $queryService = new CategoryQueryService(new PdoCategoryReadQuery($pdo, $clock));
    $managementReader = new PdoCategoryManagementReadQuery($pdo, $clock);
    $managementService = new CategoryManagementQueryService($managementReader);

    $categoryId = $commandService->create(new CreateCategoryCommand('standalone-consumer-category'));
    $contentId = $commandService->createContent(
        new CreateCategoryContentCommand($categoryId, null, 'Standalone Category', null),
    );
    $localizedContentId = $commandService->createContent(
        new CreateCategoryContentCommand($categoryId, 'en-US', 'Standalone Category English', null),
    );
    $imageAssignmentId = $commandService->createImageAssignment(
        new CreateCategoryImageAssignmentCommand($categoryId, 700),
    );
    $secondImageAssignmentId = $commandService->createImageAssignment(
        new CreateCategoryImageAssignmentCommand($categoryId, 702),
    );
    $localizedImageAssignmentId = $commandService->createImageAssignment(
        new CreateCategoryImageAssignmentCommand($categoryId, 700, 'en-US', 'web'),
    );
    $imageRoleId = $commandService->createImageRole(
        new CreateCategoryImageRoleCommand('gallery'),
    );
    $roleImageAssignmentId = $commandService->createImageAssignment(
        new CreateCategoryImageAssignmentCommand($categoryId, 701, 'en-US', 'web', $imageRoleId),
    );
    $contentFieldId = $commandService->createContentField(
        new CreateCategoryContentFieldCommand(
            $categoryId,
            'badge_config',
            'en-US',
            'web',
            CategoryContentFieldFormatEnum::JSON,
            '{"enabled":true}',
        ),
    );
    standalone_consumer_require(
        $categoryId > 0
        && $contentId > 0
        && $localizedContentId > 0
        && $imageAssignmentId > 0
        && $secondImageAssignmentId > 0
        && $localizedImageAssignmentId > 0
        && $imageRoleId > 0
        && $roleImageAssignmentId > 0
        && $contentFieldId > 0,
        'Standalone mutation returned invalid IDs.',
    );

    $commandService->setImageAssignmentDefault(
        new SetCategoryImageAssignmentDefaultCommand($imageAssignmentId),
    );
    $commandService->setImageAssignmentDefault(
        new SetCategoryImageAssignmentDefaultCommand($secondImageAssignmentId),
    );
    standalone_consumer_require(
        !$managementService->getImageAssignmentById($imageAssignmentId)->isDefault
        && $managementService->getImageAssignmentById($secondImageAssignmentId)->isDefault,
        'Standalone default assignment switch did not clear the previous default.',
    );
    $commandService->clearImageAssignmentDefault(
        new ClearCategoryImageAssignmentDefaultCommand($secondImageAssignmentId),
    );
    standalone_consumer_require(
        !$managementService->getImageAssignmentById($secondImageAssignmentId)->isDefault,
        'Standalone default clear did not remove the explicit default.',
    );
    $commandService->setImageAssignmentDefault(
        new SetCategoryImageAssignmentDefaultCommand($imageAssignmentId),
    );

    $category = $queryService->getById($categoryId);
    standalone_consumer_require($category->id === $categoryId, 'Standalone query returned the wrong Category.');
    standalone_consumer_require($category->code === 'standalone-consumer-category', 'Standalone Category code mismatch.');
    standalone_consumer_require(
        $category->createdAt->getTimezone()->getName() === 'Africa/Cairo',
        'Standalone hydration did not use the Host Clock timezone.',
    );
    $createdAtStatement = $pdo->prepare(
        'SELECT `created_at` FROM `maa_category_categories` WHERE `id` = :id',
    );
    $createdAtStatement->execute(['id' => $categoryId]);
    $storedCreatedAt = $createdAtStatement->fetchColumn();
    standalone_consumer_require(
        is_string($storedCreatedAt)
        && $storedCreatedAt === $category->createdAt->format('Y-m-d H:i:s'),
        'Standalone persistence did not preserve the Host timestamp value.',
    );
    standalone_consumer_require(
        $queryService->listRootCategories(new CategoryVisibleListCriteriaDTO(maxResults: 10))->count() === 1,
        'Standalone visible root query did not return the stored Category.',
    );
    standalone_consumer_require(
        $queryService->listChildren($categoryId, new CategoryVisibleListCriteriaDTO(maxResults: 10))->count() === 0,
        'Standalone visible child query returned an unexpected Category.',
    );
    standalone_consumer_require(
        $queryService->listContents($categoryId, new CategoryVisibleListCriteriaDTO(maxResults: 10))->count() === 2,
        'Standalone query did not return both unlocalized and localized Content.',
    );
    standalone_consumer_require(
        $queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())->count() === 2,
        'Standalone exact unlocalized Image Assignment query returned the wrong rows.',
    );
    standalone_consumer_require(
        $queryService->listImageAssignments(
            $categoryId,
            new CategoryImageAssignmentScopeDTO('en-US', 'web'),
        )->count() === 1,
        'Standalone exact localized/platform Image Assignment query returned the wrong rows.',
    );
    standalone_consumer_require(
        $queryService->listImageAssignments(
            $categoryId,
            new CategoryImageAssignmentScopeDTO('en-US', 'web', $imageRoleId),
        )->count() === 1,
        'Standalone exact Role-scoped Image Assignment query returned the wrong rows.',
    );
    $visibleContentFields = $queryService->listContentFields(
        $categoryId,
        new CategoryContentFieldScopeDTO('en-US', 'web'),
        new CategoryVisibleListCriteriaDTO(maxResults: 10),
    );
    $visibleContentFieldId = null;
    foreach ($visibleContentFields as $visibleContentField) {
        $visibleContentFieldId = $visibleContentField->id;
    }
    standalone_consumer_require(
        $visibleContentFields->count() === 1 && $visibleContentFieldId === $contentFieldId,
        'Standalone exact Content Field consumer query returned the wrong rows.',
    );

    $managementCategory = $managementService->getById(
        $categoryId,
        CategoryDeletedStateEnum::NON_DELETED,
    );
    standalone_consumer_require(
        $managementCategory->id === $categoryId,
        'Standalone management read service returned the wrong Category.',
    );
    $managementCategories = $managementService->listCategories(
        new CategoryListCriteriaDTO(
            status: CategoryStatusEnum::ACTIVE,
            deletedState: CategoryDeletedStateEnum::NON_DELETED,
            maxResults: 10,
        ),
    );
    standalone_consumer_require(
        $managementCategories->count() === 1,
        'Standalone management Category list did not return the stored Category.',
    );
    standalone_consumer_require(
        $managementService->listRootCategories(new CategoryListCriteriaDTO(maxResults: 10))->count() === 1,
        'Standalone management root list did not return the stored Category.',
    );
    standalone_consumer_require(
        $managementService->listChildren($categoryId, new CategoryListCriteriaDTO(maxResults: 10))->count() === 0,
        'Standalone management child list returned an unexpected Category.',
    );
    $managementContent = $managementService->getContentById(
        $contentId,
        CategoryDeletedStateEnum::NON_DELETED,
    );
    standalone_consumer_require(
        $managementContent->id === $contentId,
        'Standalone management read service returned the wrong Content.',
    );
    standalone_consumer_require(
        $managementContent->languageCode === null,
        'Standalone management read did not preserve the unlocalized NULL language identity.',
    );
    standalone_consumer_require(
        $managementService->listContents(
            new CategoryContentListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::NON_DELETED,
                maxResults: 10,
            ),
        )->count() === 2,
        'Standalone management Content list did not return both Content records.',
    );
    standalone_consumer_require(
        $managementService->getImageAssignmentById($imageAssignmentId)->id === $imageAssignmentId,
        'Standalone management read service returned the wrong Image Assignment.',
    );
    standalone_consumer_require(
        $managementService->getImageAssignmentById($imageAssignmentId)->isDefault
        && !$managementService->getImageAssignmentById($secondImageAssignmentId)->isDefault
        && !$managementService->getImageAssignmentById($localizedImageAssignmentId)->isDefault
        && !$managementService->getImageAssignmentById($roleImageAssignmentId)->isDefault,
        'Standalone management hydration returned the wrong Image Assignment default state.',
    );
    standalone_consumer_require(
        $managementService->listImageAssignments(
            new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId),
        )->count() === 4,
        'Standalone management Image Assignment list did not return all scopes.',
    );
    standalone_consumer_require(
        $managementService->getImageRoleById($imageRoleId)->roleKey === 'gallery',
        'Standalone management Role read returned the wrong Role.',
    );
    standalone_consumer_require(
        $managementService->getImageRoleByKey('gallery')->id === $imageRoleId,
        'Standalone management Role resolve returned the wrong Role.',
    );
    standalone_consumer_require(
        $managementService->listImageRoles(
            new CategoryImageRoleListCriteriaDTO(
                status: CategoryImageRoleStatusEnum::ACTIVE,
                maxResults: 10,
            ),
        )->count() === 1,
        'Standalone management Role list did not return the stored Role.',
    );
    $managementContentField = $managementService->getContentFieldById($contentFieldId);
    standalone_consumer_require(
        $managementContentField->id === $contentFieldId
        && $managementContentField->fieldKey === 'badge_config'
        && $managementContentField->format === CategoryContentFieldFormatEnum::JSON,
        'Standalone management read service returned the wrong Content Field.',
    );
    standalone_consumer_require(
        $managementService->listContentFields(
            new CategoryContentFieldListCriteriaDTO(
                categoryId: $categoryId,
                scope: new CategoryContentFieldScopeDTO('en-US', 'web'),
                maxResults: 10,
            ),
        )->count() === 1,
        'Standalone management Content Field list did not return the exact scope.',
    );
    $commandService->updateStatus(
        new \Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand(
            $categoryId,
            CategoryStatusEnum::INACTIVE,
        ),
    );
    $updatedCategory = $managementService->getById($categoryId, CategoryDeletedStateEnum::NON_DELETED);
    $updatedAtStatement = $pdo->prepare(
        'SELECT `updated_at` FROM `maa_category_categories` WHERE `id` = :id',
    );
    $updatedAtStatement->execute(['id' => $categoryId]);
    $storedUpdatedAt = $updatedAtStatement->fetchColumn();
    standalone_consumer_require(
        $updatedCategory->updatedAt->getTimezone()->getName() === 'Africa/Cairo'
        && is_string($storedUpdatedAt)
        && $storedUpdatedAt === $updatedCategory->updatedAt->format('Y-m-d H:i:s'),
        'Standalone update did not preserve Host timezone semantics.',
    );
} finally {
    standalone_consumer_drop_schema($pdo);
}

fwrite(
    STDOUT,
    sprintf("Standalone external consumer verification passed for %d public package types.\n", count($publicTypes)),
);
