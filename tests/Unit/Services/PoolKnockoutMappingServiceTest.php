<?php

namespace Tests\Unit\Services;

use App\Services\PoolKnockoutMappingService;
use Tests\TestCase;

class PoolKnockoutMappingServiceTest extends TestCase
{
    private function makeQualifiers(): array
    {
        // Build fake qualifiers: 8 pools, 4 players each, player_id = poolIndex*10 + rank
        $pools = ['A','B','C','D','E','F','G','H'];
        $qualifiers = [];
        foreach ($pools as $pi => $pool) {
            $qualifiers[$pool] = [];
            for ($rank = 1; $rank <= 4; $rank++) {
                $qualifiers[$pool][] = [
                    'player_id' => $pi * 10 + $rank,
                    'pool_name' => $pool,
                    'rank'      => $rank,
                    'name'      => "Player {$pool}{$rank}",
                ];
            }
        }
        return $qualifiers;
    }

    public function test_it_returns_16_pairs(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        $this->assertCount(16, $pairs);
    }

    public function test_position_0_is_a1_vs_c4(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[0];
        $this->assertSame('A1', $a['source']);
        $this->assertSame('C4', $b['source']);
        // player_id for A1 = 0*10+1 = 1; C4 = 2*10+4 = 24
        $this->assertSame(1, $a['player_id']);
        $this->assertSame(24, $b['player_id']);
    }

    public function test_position_1_is_a2_vs_c3(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[1];
        $this->assertSame('A2', $a['source']);
        $this->assertSame('C3', $b['source']);
    }

    public function test_position_2_is_a3_vs_c2(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[2];
        $this->assertSame('A3', $a['source']);
        $this->assertSame('C2', $b['source']);
    }

    public function test_position_3_is_a4_vs_c1(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[3];
        $this->assertSame('A4', $a['source']);
        $this->assertSame('C1', $b['source']);
    }

    public function test_position_4_is_b1_vs_d4(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[4];
        $this->assertSame('B1', $a['source']);
        $this->assertSame('D4', $b['source']);
    }

    public function test_position_5_is_b2_vs_d3(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[5];
        $this->assertSame('B2', $a['source']);
        $this->assertSame('D3', $b['source']);
    }

    public function test_position_8_is_e1_vs_g4(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[8];
        $this->assertSame('E1', $a['source']);
        $this->assertSame('G4', $b['source']);
    }

    public function test_position_12_is_f1_vs_h4(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[12];
        $this->assertSame('F1', $a['source']);
        $this->assertSame('H4', $b['source']);
    }

    public function test_position_15_is_f4_vs_h1(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        [$a, $b] = $pairs[15];
        $this->assertSame('F4', $a['source']);
        $this->assertSame('H1', $b['source']);
    }

    public function test_top_half_positions_0_to_7_are_ac_and_bd(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        foreach (range(0, 3) as $i) {
            $this->assertStringStartsWith('A', $pairs[$i][0]['source']);
            $this->assertStringStartsWith('C', $pairs[$i][1]['source']);
        }
        foreach (range(4, 7) as $i) {
            $this->assertStringStartsWith('B', $pairs[$i][0]['source']);
            $this->assertStringStartsWith('D', $pairs[$i][1]['source']);
        }
    }

    public function test_bottom_half_positions_8_to_15_are_eg_and_fh(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        foreach (range(8, 11) as $i) {
            $this->assertStringStartsWith('E', $pairs[$i][0]['source']);
            $this->assertStringStartsWith('G', $pairs[$i][1]['source']);
        }
        foreach (range(12, 15) as $i) {
            $this->assertStringStartsWith('F', $pairs[$i][0]['source']);
            $this->assertStringStartsWith('H', $pairs[$i][1]['source']);
        }
    }

    public function test_it_fails_if_a_pool_is_missing(): void
    {
        $service = new PoolKnockoutMappingService();
        $qualifiers = $this->makeQualifiers();
        unset($qualifiers['E']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Pool E is missing/');
        $service->buildPairs($qualifiers);
    }

    public function test_it_fails_if_a_pool_has_fewer_than_4_qualifiers(): void
    {
        $service = new PoolKnockoutMappingService();
        $qualifiers = $this->makeQualifiers();
        $qualifiers['B'] = array_slice($qualifiers['B'], 0, 3); // only 3

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Pool B has fewer than 4 qualifiers/');
        $service->buildPairs($qualifiers);
    }

    public function test_source_labels_are_correct_format(): void
    {
        $service = new PoolKnockoutMappingService();
        $labels = $service->getSourceLabelMap();
        $this->assertCount(16, $labels);
        $this->assertSame('A1 vs C4', $labels[0]);
        $this->assertSame('A4 vs C1', $labels[3]);
        $this->assertSame('B1 vs D4', $labels[4]);
        $this->assertSame('F4 vs H1', $labels[15]);
    }

    public function test_all_pairs_have_different_players(): void
    {
        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($this->makeQualifiers());
        $allPlayerIds = [];
        foreach ($pairs as [$a, $b]) {
            $allPlayerIds[] = $a['player_id'];
            $allPlayerIds[] = $b['player_id'];
        }
        $this->assertCount(32, array_unique($allPlayerIds), 'All 32 players should appear exactly once');
    }
}
