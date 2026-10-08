<?php

declare(strict_types=1);

/**
 * Generates ext_emconf.php from composer.json for a TER release.
 *
 * ext_emconf.php is not kept in the repository (not needed in composer mode),
 * but typo3/tailor requires it with a matching version when publishing to TER.
 *
 * Usage: php Build/Scripts/generateExtEmconf.php <version>
 */
$version = $argv[1] ?? '';
if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $version)) {
    fwrite(STDERR, 'Usage: php Build/Scripts/generateExtEmconf.php <x.y.z>' . PHP_EOL);
    exit(1);
}

$rootPath = dirname(__DIR__, 2);
$composer = json_decode((string)file_get_contents($rootPath . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

/**
 * Converts a composer constraint like "^14.3" or ">=8.3" into a TER range like "14.3.0-14.99.99".
 */
$toTerRange = static function (string $constraint): string {
    if (!preg_match('/(\d+)(?:\.(\d+))?(?:\.(\d+))?/', $constraint, $matches)) {
        throw new RuntimeException(sprintf('Unsupported version constraint "%s"', $constraint));
    }
    $major = (int)$matches[1];
    $minor = (int)($matches[2] ?? 0);
    $patch = (int)($matches[3] ?? 0);
    return sprintf('%d.%d.%d-%d.99.99', $major, $minor, $patch, $major);
};

$depends = [];
foreach ($composer['require'] ?? [] as $package => $constraint) {
    if ($package === 'php') {
        $depends['php'] = $toTerRange($constraint);
    } elseif ($package === 'typo3/cms-core') {
        $depends['typo3'] = $toTerRange($constraint);
    } elseif (str_starts_with($package, 'typo3/cms-')) {
        $depends[substr($package, strlen('typo3/cms-'))] = $toTerRange($constraint);
    }
}

$emConf = [
    'title' => 'Short URLs',
    'description' => $composer['description'] ?? '',
    'category' => 'be',
    'state' => 'stable',
    'author' => $composer['authors'][0]['name'] ?? '',
    'author_company' => 'UEBERBIT GmbH',
    'version' => $version,
    'constraints' => [
        'depends' => $depends,
        'conflicts' => [],
        'suggests' => [],
    ],
];

$content = '<?php' . PHP_EOL . PHP_EOL . '$EM_CONF[$_EXTKEY] = ' . var_export($emConf, true) . ';' . PHP_EOL;
file_put_contents($rootPath . '/ext_emconf.php', $content);

echo 'Generated ext_emconf.php for version ' . $version . PHP_EOL;
