<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityWithRawDataTest extends TestCase
{
    use MatchesSnapshots;

    public function testGetSplits(): void
    {
        $activityWithRawData = ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()->build(),
            rawData: $this->buildRawData(),
        );

        $this->assertMatchesJsonSnapshot(Json::encode($activityWithRawData->getSplits()));
    }

    private function buildRawData(): array
    {
        return Json::decode('{"resource_state":3,"athlete":{"id":5041064,"resource_state":1},"name":"Plataforma de Gredos-Laguna Grande de Gredos","distance":16000.3,"moving_time":15027,"elapsed_time":21973,"total_elevation_gain":696.3,"type":"Hike","sport_type":"Hike","id":10285487022,"start_date":"2023-11-26T09:06:46Z","start_date_local":"2023-11-26T10:06:46Z","timezone":"(GMT+01:00) Europe/Madrid","utc_offset":3600,"location_city":null,"location_state":null,"location_country":"Spain","achievement_count":0,"kudos_count":23,"comment_count":1,"athlete_count":2,"photo_count":0,"trainer":false,"commute":false,"manual":false,"private":false,"visibility":"everyone","flagged":false,"gear_id":null,"start_latlng":[40.275539522990584,-5.232091415673494],"end_latlng":[40.27553885243833,-5.232109604403377],"average_speed":1.065,"max_speed":3.068,"has_heartrate":false,"heartrate_opt_out":false,"display_hide_heartrate_option":false,"elev_high":2169.8,"elev_low":1755.2,"upload_id":11012806629,"upload_id_str":"11012806629","external_id":"F05C1F6F-8178-4FEF-9532-29419116580A-activity.fit","from_accepted_tag":false,"pr_count":0,"total_photo_count":5,"has_kudoed":false,"description":"","calories":1425.3,"perceived_exertion":null,"prefer_perceived_exertion":null,"splits_metric":[{"distance":1000.7,"elapsed_time":1215,"elevation_difference":131.2,"moving_time":895,"split":1,"average_speed":1.12,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.1,"elapsed_time":1052,"elevation_difference":60.1,"moving_time":912,"split":2,"average_speed":1.1,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.4,"elapsed_time":1336,"elevation_difference":143.1,"moving_time":982,"split":3,"average_speed":1.02,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":996.9,"elapsed_time":1010,"elevation_difference":78.1,"moving_time":935,"split":4,"average_speed":1.07,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1003.1,"elapsed_time":1968,"elevation_difference":-95.2,"moving_time":924,"split":5,"average_speed":1.09,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":999.2,"elapsed_time":1034,"elevation_difference":-62.8,"moving_time":839,"split":6,"average_speed":1.19,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":998.4,"elapsed_time":3037,"elevation_difference":-71.2,"moving_time":941,"split":7,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.3,"elapsed_time":1127,"elevation_difference":8.9,"moving_time":884,"split":8,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.6,"elapsed_time":1683,"elevation_difference":-5.4,"moving_time":1065,"split":9,"average_speed":0.94,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":998.8,"elapsed_time":1233,"elevation_difference":76.3,"moving_time":1116,"split":10,"average_speed":0.89,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.3,"elapsed_time":1164,"elevation_difference":69.8,"moving_time":1125,"split":11,"average_speed":0.89,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.1,"elapsed_time":1974,"elevation_difference":78.7,"moving_time":965,"split":12,"average_speed":1.04,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":997.4,"elapsed_time":901,"elevation_difference":-85.5,"moving_time":794,"split":13,"average_speed":1.26,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":999.9,"elapsed_time":1102,"elevation_difference":-137,"moving_time":975,"split":14,"average_speed":1.03,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.1,"elapsed_time":973,"elevation_difference":-68.3,"moving_time":728,"split":15,"average_speed":1.37,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000,"elapsed_time":1162,"elevation_difference":-113.2,"moving_time":945,"split":16,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":0,"elapsed_time":2,"elevation_difference":-0.2,"moving_time":2,"split":17,"average_speed":0,"average_grade_adjusted_speed":null,"pace_zone":0}],"splits_standard":[{"distance":1611.6,"elapsed_time":1759,"elevation_difference":166.2,"moving_time":1422,"split":1,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.2,"elapsed_time":2086,"elevation_difference":193.3,"moving_time":1567,"split":2,"average_speed":1.03,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.8,"elapsed_time":2543,"elevation_difference":-17.2,"moving_time":1523,"split":3,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.9,"elapsed_time":1712,"elevation_difference":-133.9,"moving_time":1371,"split":4,"average_speed":1.17,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1610.8,"elapsed_time":3719,"elevation_difference":-13.7,"moving_time":1469,"split":5,"average_speed":1.1,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.3,"elapsed_time":2421,"elevation_difference":28.7,"moving_time":1715,"split":6,"average_speed":0.94,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1609,"elapsed_time":1913,"elevation_difference":141.1,"moving_time":1828,"split":7,"average_speed":0.88,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1609.5,"elapsed_time":2473,"elevation_difference":-25.7,"moving_time":1389,"split":8,"average_speed":1.16,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1611.6,"elapsed_time":1694,"elevation_difference":-176.2,"moving_time":1432,"split":9,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1513.6,"elapsed_time":1653,"elevation_difference":-155.2,"moving_time":1311,"split":10,"average_speed":1.15,"average_grade_adjusted_speed":null,"pace_zone":0}],"laps":[{"id":35376863372,"resource_state":2,"name":"Lap 1","activity":{"id":10285487022,"visibility":"everyone","resource_state":1},"athlete":{"id":5041064,"resource_state":1},"elapsed_time":21974,"moving_time":21974,"start_date":"2023-11-26T09:06:46Z","start_date_local":"2023-11-26T10:06:46Z","distance":16000.3,"average_speed":0.73,"max_speed":3.068,"lap_index":1,"split":1,"start_index":0,"end_index":21193,"total_elevation_gain":696.3,"device_watts":false}],"photos":{"primary":{"unique_id":"A2A7F408-BCBE-43CC-B021-066147771956","urls":{"100":"https://dgtzuqphqg23d.cloudfront.net/oroacx2aRch5HegYTVXUq8beRUIj745RfW256HinTec-128x96.jpg","600":"https://dgtzuqphqg23d.cloudfront.net/oroacx2aRch5HegYTVXUq8beRUIj745RfW256HinTec-768x576.jpg"},"source":1,"media_type":1},"use_primary_photo":false,"count":5},"stats_visibility":[{"type":"heart_rate","visibility":"everyone"},{"type":"pace","visibility":"everyone"},{"type":"power","visibility":"everyone"},{"type":"speed","visibility":"everyone"},{"type":"calories","visibility":"everyone"}],"hide_from_home":false,"device_name":"Strava iPhone App","embed_token":"85cca5861cdb019b9bdd3c55c3abd438e900d59c","available_zones":[],"localImagePaths":["files/activities/c56c8152-c805-4c02-a680-c6ab3cbe7a6f.jpg","files/activities/1a7b3e42-98a6-432b-acf2-f4ff7a0d1281.jpg","files/activities/5d74f40a-57e6-442f-b1b6-513bb9df28fd.jpg","files/activities/6f94bd9e-598b-46cf-a2d8-b7a30c492bf9.jpg","files/activities/7bc2c94d-a86e-4602-97cc-2ac484f3ffd4.jpg"]}');
    }
}