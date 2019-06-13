<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class CommandHelperTest extends TestCase
{

	public function dataBegin(): array
	{
		return [
			[
				'',
				'',
				__DIR__ . '/data/testIncludesExpand.neon',
				null,
				null,
				[
					'level' => 'max',
				],
				false,
			],
			[
				'',
				'Recursive included file',
				__DIR__ . '/data/1.neon',
				null,
				null,
				[],
				true,
			],
			[
				'',
				'does not exist',
				__DIR__ . '/data/nonexistent.neon',
				null,
				null,
				[],
				true,
			],
			[
				'',
				'is missing or is not readable',
				__DIR__ . '/data/containsNonexistent.neon',
				null,
				null,
				[],
				true,
			],
			[
				'',
				'These files are included multiple times',
				__DIR__ . '/../../../conf/config.level7.neon',
				'7',
				null,
				[],
				true,
			],
			[
				'',
				'These files are included multiple times',
				__DIR__ . '/../../../conf/config.level7.neon',
				'6',
				null,
				[],
				true,
			],
			[
				'',
				'These files are included multiple times',
				__DIR__ . '/../../../conf/config.level6.neon',
				'7',
				null,
				[],
				true,
			],
			[
				'',
				'',
				__DIR__ . '/data/includePhp.neon',
				null,
				null,
				[
					'level' => '3',
				],
				false,
			],
			[
				'',
				'',
				__DIR__ . '/data/includePhp.neon',
				null,
				'/no/such/directory',
				[],
				true,
			],
		];
	}

	/**
	 * @dataProvider dataBegin
	 * @param string $input
	 * @param string $expectedOutput
	 * @param string|null $projectConfigFile
	 * @param string|null $level
	 * @param string|null $workingDir
	 * @param mixed[] $expectedParameters
	 * @param bool $expectException
	 */
	public function testBegin(
		string $input,
		string $expectedOutput,
		?string $projectConfigFile,
		?string $level,
		?string $workingDir,
		array $expectedParameters,
		bool $expectException
	): void
	{
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$output = new StreamOutput($resource);

		try {
			$result = CommandHelper::begin(
				new StringInput($input),
				$output,
				[__DIR__],
				null,
				null,
				null,
				$projectConfigFile,
				$level,
				$workingDir
			);
			if ($expectException) {
				$this->fail();
			}
		} catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
			if (!$expectException) {
				throw $e;
			}
		}

		rewind($output->getStream());

		$contents = stream_get_contents($output->getStream());
		if ($contents === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$this->assertContains($expectedOutput, $contents);

		if (isset($result)) {
			$parameters = $result->getContainer()->parameters;
			foreach ($expectedParameters as $name => $expectedValue) {
				$this->assertArrayHasKey($name, $parameters);
				$this->assertSame($expectedValue, $parameters[$name]);
			}
		} else {
			$this->assertCount(0, $expectedParameters);
		}
	}

}
