<?php

namespace App\Tests\Domain\Strava\Activity\Stream\CalculateBestStreamAverages;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class CalculateBestStreamAveragesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(4))
            ->withStreamType(StreamType::WATTS)
            ->withData(Json::decode('[197,188,212,239,231,215,242,203,216,248,210,229,203,202,197,221,230,214,230,258,220,250,220,232,206,227,209,220,232,238,235,240,236,228,248,246,251,220,215,95,159,166,256,242,257,245,230,209,225,205,191,178,168,169,202,180,161,137,76,222,154,167,123,135,126,149,131,132,173,178,173,145,117,97,176,174,191,265,223,225,189,212,205,237,201,213,217,181,195,183,183,187,201,196,233,219,206,201,150,189,173,197,172,162,129,179,201,217,222,218,215,192,208,234,220,235,234,216,208,181,187,212,197,200,186,213,189,210,216,199,210,197,170,175,184,199,212,199,215,236,244,229,234,215,232,253,239,279,238,214,274,242,252,241,270,224,219,209,216,208,248,247,235,269,271,275,309,290,272,288,268,247,224,213,249,225,217,254,263,262,238,214,241,183,187,177,206,196,201,205,216,207,213,221,231,245,220,246,235,253,242,242,255,253,246,233,261,228,221,219,233,230,204,219,226,220,197,249,248,232,259,272,294,266,287,279,259,267,278,276,278,257,280,276,267,295,326,322,328,302,359,379,319,303,293,355,427,479,355,403,428,484,450,410,436,425,437,445,438,443,407,370,374,369,358,314,315,316,313,279,380,274,336,319,334,347,310,313,321,361,333,354,342,363,354,341,267,236,252,216,237,214,231,244,278,280,283,288,325,262,248,247,212,195,131,149,134,150,116,81,107,113,104,129,148,162,194,173,213,280,292,254,266,285,252,244,219,216,217,172,177,211,176,227,189,206,207,220,207,217,200,202,222,238,208,238,228,234,243,222,217,217,214,213,223,224,218,215,220,205,225,228,237,241,231,222,204,226,223,215,209,201,173,206,183,249,218,231,233,219,231,236,230,234,234,240,235,218,209,184,150,169,193,205,219,243,283,218,224,199,228,253,206,201,197,208,225,250,316,344,441,404,384,324,286,307,313,194,282,392,491,503,516,422,474,478,428,433,429,423,430,413,387,367,440,429,410,420,387,401,413,369,410,423,412,400,369,135,177,244,231,247,297,305,302,309,317,276,270,287,279,273,282,293,313,264,279,256,250,264,238,194,173,187,146,119,112,138,92,135,120,71,77,124,106,167,168,165,177,191,182,194,193,205,203,217,217,176,193,206,162,132,146,149,223,172,182,206,213,253,209,281,277,323,416,371,375,329,302,261,221,241,238,223,288,234,208,211,235,196,276,272,260,230,241,210,222,217,227,226,225,230,228,237,161,210,190,202,188,187,210,197,169,272,397,329,313,281,316,306,305,277,293,266,278,258,293,259,271,273,287,270,203,249,199,246,253,272,238,254,234,217,224,246,236,225,238,208,209,206,216,211,212,218,227,238,227,246,227,232,229,217,233,229,230,221,226,244,214,205,222,222,207,209,207,195,192,221,193,209,205,209,221,219,219,220,216,253,227,185,193,219,218,244,201,214,206,198,190,191,187,151,181,204,219,219,228,208,223,204,210,204,203,200,178,176,163,163,142,150,152,215,252,252,229,215,229,302,281,360,261,221,243,224,226,219,223,218,225,237,240,254,262,257,255,213,240,231,234,222,231,222,214,220,211,237,213,247,235,254,266,266,248,246,260,240,229,243,244,241,257,239,231,237,217,211,226,230,204,205,221,238,243,257,250,244,250,226,258,252,261,330,298,267,294,322,292,274,297,321,297,328,318,298,316,321,314,286,306,311,303,319,295,255,210,121,88,107,116,132,208,188,275,257,257,268,244,231,246,249,237,220,218,213,201,192,223,220,206,206,198,209,214,232,226,235,237,228,245,209,271,231,265,234,236,223,202,205,197,196,201,214,208,207,189,198,178,183,185,190,169,192,199,215,201,220,212,231,242,251,246,235,241,263,262,248,221,242,240,225,213,178,200,207,211,211,217,212,194,223,191,208,177,205,216,256,188,207,207,203,207,188,197,196,192,221,201,188,198,187,194,200,210,194,213,217,224,230,227,218,223,239,237,211,236,242,222,219,242,230,220,219,236,233,183,210,212,209,204,215,217,201,210,200,216,197,222,204,221,205,240,243,234,232,221,229,221,214,207,223,199,222,211,211,213,203,212,222,193,198,199,203,201,187,229,250,255,255,289,285,271,251,256,257,238,222,236,238,256,250,252,232,233,212,184,185,190,191,216,227,225,232,233,232,230,241,234,209,184,148,178,184,163,201,184,187,176,182,191,195,197,181,181,192,165,178,142,150,146,160,159,168,190,214,196,203,192,217,249,229,242,251,243,235,238,220,236,220,230,229,221,218,192,209,200,236,234,243,244,295,262,233,247,225,250,254,240,224,231,235,237,246,261,263,270,253,279,270,254,247,235,245,241,226,249,234,230,242,214,239,226,234,239,263,248,246,241,240,271,249,241,232,242,227,247,232,219,229,203,194,213,215,204,195,211,209,236,207,206,151,241,162,177,193,148,153,217,168,181,187,181,183,186,187,186,185,209,212,204,216,196,208,197,195,176,173,195,198,195,213,217,223,228,261,267,294,285,348,317,332,335,300,269,300,204,167,176,193,196,186,176,184,164,176,175,190,205,205,209,196,208,203,211,220,233,230,246,220,222,178,174,174,183,183,202,203,160,233,209,215,212,212,229,235,235,237,233,188,218,218,193,202,183,216,195,218,217,225,224,183,173,206,207,200,208,213,199,216,206,208,208,199,213,210,214,204,211,207,208,223,217,190,218,219,203,211,212,241,232,211,228,220,207,197,183,203,199,210,180,178,172,170,181,136,180,205,212,190,183,201,202,196,201,213,217,174,182,178,202,220,236,267,245,240,264,232,245,270,258,286,310,315,284,296,299,358,332,341,337,362,358,358,344,334,387,383,344,339,318,319,301,352,368,328,344,343,375,318,345,345,357,349,323,343,360,359,351,354,339,356,369,350,349,337,342,343,345,345,340,336,331,301,326,221,234,272,237,248,214,230,205,204,184,187,111,108,126,116,148,140,200,226,162,240,238,220,211,201,172,187,180,180,162,181,180,184,176,176,164,185,183,198,195,188,185,188,178,157,159,174,203,195,180,222,261,312,377,365,344,318,284,268,252,233,260,220,202,176,137,179,115,201,198,196,183,182,190,172,181,188,215,213,210,230,150,189,207,206,207,220,207,190,194,207,214,214,210,199,219,205,204,214,211,210,195,215,179,184,149,171,135,160,130,163,138,159,182,194,194,191,166,164,170,165,140,208,200,208,215,193,168,246,212,192,208,207,214,235,227,233,231,220,195,214,221,225,225,208,206,200,190,192,212,220,198,191,172,194,188,203,227,215,217,205,242,263,208,242,190,183,149,182,175,170,190,201,181,195,197,203,197,196,184,183,162,176,193,234,268,306,301,279,217,224,132,78,149,143,157,164,165,187,196,197,206,167,214,180,195,190,201,187,196,182,202,195,199,206,209,201,196,174,194,182,220,204,210,207,205,216,199,223,195,197,193,219,222,245,276,215,220,204,209,191,193,204,210,203,196,198,210,215,198,215,201,199,205,190,206,200,197,184,188,181,187,196,200,201,212,188,196,200,190,188,201,194,194,186,202,195,211,189,212,228,250,240,245,195,208,202,225,206,217,226,198,223,224,235,239,255,241,219,209,174,202,179,211,191,168,157,158,163,167,148,182,192,203,168,204,215,220,192,198,204,206,221,184,216,205,182,165,184,179,180,179,193,172,188,188,194,188,190,182,190,189,207,211,211,196,176,173,184,179,201,201,194,180,196,186,185,185,197,191,208,222,210,204,200,181,113,128,134,102,135,143,213,194,207,200,214,223,193,206,209,206,216,205,227,209,211,217,190,189,203,185,190,196,200,182,187,192,163,189,172,179,160,176,163,177,183,190,194,183,173,203,181,173,219,199,183,181,175,155,172,148,148,177,176,198,170,170,182,205,181,184,183,183,182,191,182,191,201,191,188,206,195,206,198,205,185,219,205,219,218,219,207,206,216,210,198,195,187,196,192,190,173,179,182,226,185,166,164,159,172,194,176,184,176,179,161,171,161,159,182,183,186,204,200,205,195,222,202,211,223,217,238,218,202,201,194,195,166,145,166,185,180,196,175,185,184,182,201,200,221,217,211,211,250,213,203,196,210,230,193,216,216,211,190,194,180,180,162,183,176,153,154,160,160,196,180,179,185,179,186,179,188,169,166,162,168,158,174,232,243,231,212,206,206,201,215,231,208,201,212,197,198,200,172,200,193,204,190,192,183,217,204,206,206,177,215,189,183,189,196,199,210,211,202,214,213,194,232,222,235,212,214,223,236,230,204,201,213,210,206,200,206,180,210,209,208,201,191,202,203,218,194,199,195,182,177,183,196,183,182,204,208,202,192,194,199,197,202,206,190,198,207,193,190,196,193,193,174,170,167,163,160,156,197,176,191,191,164,208,170,172,183,173,193,196,205,203,188,199,190,198,186,186,198,191,205,179,198,219,191,180,179,192,144,168,178,206,197,202,201,189,209,212,187,197,203,200,196,211,199,185,199,189,207,184,194,215,198,203,178,185,192,172,181,182,177,187,194,193,233,234,200,218,221,217,195,203,197,213,208,189,199,201,196,191,191,211,204,196,191,206,195,191,191,201,207,199,203,191,183,187,185,201,189,190,195,183,173,192,174,205,207,229,201,210,198,212,197,205,200,193,189,190,216,220,217,215,198,192,188,171,170,206,172,195,183,221,213,227,232,210,227,214,231,221,208,209,211,180,207,174,216,179,222,216,222,198,193,199,156,171,156,190,186,190,188,168,173,195,244,249,271,267,286,329,240,250,256,260,261,278,290,220,238,293,268,308,404,343,366,335,352,325,306,311,321,335,302,332,345,293,332,329,312,326,322,330,302,295,311,301,327,324,303,299,353,282,299,321,276,257,314,325,340,333,292,271,289,326,286,296,298,299,268,255,192,317,284,408,363,404,269,319,276,264,252,218,224,198,202,162,157,142,104,148,148,108,90,77,95,107,105,109,120,153,120,139,302,330,325,364,327,230,164,134,143,141,93,91,113,117,120,128,202,303,326,493,422,482,540,481,470,491,89,61,61,71,46,15,50,212,219,187,242,232,288,186,205,182,177,187,162,170,178,175,180,168,196,160,187,214,191,173,213,192,220,211,203,190,176,172,137,166,157,195,185,184,187,190,188,197,223,213,203,190,214,219,233,209,208,209,191,189,164,161,169,149,168,153,183,209,205,202,206,208,210,179,172,159,169,196,201,212,198,194,196,255,174,189,211,175,166,168,174,176,293,325,257,226,232,199,189,178,178,257,211,176,171,180,406,424,437,428,426,418,365,313,276,199,174,87,64,94,80,114,138,163,134,116,110,139,123,164,194,292,236,255,230,219,171,169,190,206,172,163,172,200,175,160,155,171,180,180,172,181,191,208,243,258,256,304,242,251,240,203,223,206,231,183,209,205,197,233,206,223,227,230,204,222,226,237,277,273,264,233,222,233,236,236,234,248,221,222,223,212,194,193,167,185,179,192,194,200,197,187,245,224,251,220,222,200,201,193,191,216,216,211,194,218,202,214,194,183,202,187,174,191,186,184,183,204,225,209,223,197,211,217,211,211,207,189,180,218,180,196,184,200,186,196,203,196,206,230,193,202,227,206,204,196,199,194,201,203,184,204,203,211,208,210,201,201,214,190,207,187,196,204,188,256,192,170,180,173,177,202,188,192,232,231,209,198,179,188,183,186,182,175,194,170,182,185,180,189,171,174,169,194,190,170,180,188,178,190,207,227,216,244,262,232,254,295,260,232,249,231,250,247,233,242,224,226,225,223,201,215,181,190,223,204,210,198,199,201,209,186,180,195,191,195,204,185,176,188,183,204,265,242,242,217,218,210,183,202,179,189,206,201,197,210,205,217,207,203,227,247,222,225,226,218,247,234,246,242,239,255,244,245,250,243,234,245,234,214,214,218,212,218,197,188,207,201,210,207,197,202,201,208,192,197,189,186,196,193,195,223,208,189,192,164,165,138,183,191,181,186,146,233,219,240,271,254,237,270,241,222,199,208,208,190,221,223,217,173,180,176,187,181,189,196,180,204,207,206,216,224,253,202,232,213,176,177,156,165,174,172,179,191,183,203,179,160,171,181,170,174,181,188,204,191,177,185,177,171,174,185,190,184,200,214,215,230,241,234,248,208,222,278,328,345,360,371,325,311,272,262,256,253,292,268,252,261,253,298,284,311,308,271,317,291,283,288,262,268,268,268,265,246,280,274,307,305,273,316,298,312,343,304,246,213,116,148,151,209,207,166,223,222,219,227,230,221,203,225,204,205,197,208,196,209,185,176,204,190,187,188,170,164,181,252,206,207,189,183,179,165,168,175,178,188,190,209,202,207,198,200,225,215,210,228,248,231,217,235,219,223,202,63,149,134,142,191,224,163,184,228,221,237,247,242,255,252,235,240,225,207,208,186,194,197,182,189,160,164,158,189,183,181,185,184,219,198,229,217,210,201,214,221,198,203,206,197,205,202,191,209,195,203,202,290,270,237,232,231,181,204,199,168,155,197,191,200,205,167,205,197,197,185,209,168,207,195,200,201,204,194,213,204,193,197,203,193,210,202,190,214,205,225,202,218,242,226,233,222,220,211,155,196,183,194,198,221,217,230,233,211,217,219,254,244,219,207,184,191,188,196,197,227,229,228,223,205,196,201,170,181,197,180,194,174,163,187,207,195,223,226,235,237,216,212,239,243,279,306,222,275,240,282,291,324,326,319,346,342,310,291,258,263,266,337,308,366,351,351,346,343,328,307,306,312,331,332,342,332,350,330,337,334,342,315,328,318,331,332,322,321,311,336,321,329,328,337,320,328,308,320,322,328,333,324,345,333,325,321,302,285,210,195,187,221,218,144,149,172,167,136,116,97,151,136,135,142,138,134,162,158,150,153,195,203,171,187,178,182,174,174,189,184,167,160,173,173,180,203,182,206,230,235,246,221,260,274,309,295,278,270,261,229,202,198,184,138,220,213,208,191,162,181,166,175,165,164,162,166,158,162,175,173,224,196,209,277,268,265,228,167,168,182,181,212,192,197,226,211,214,191,191,187,198,193,193,189,193,199,203,194,202,212,199,209,221,220,229,238,210,224,228,224,216,216,224,213,229,192,186,180,179,187,180,162,181,184,185,192,191,181,178,194,207,188,205,195,206,196,196,197,193,202,194,192,190,193,195,178,190,173,172,192,188,186,185,179,165,136,166,159,173,170,165,177,182,182,156,185,154,130,163,157,173,173,172,164,167,175,165,162,168,167,162,175,153,164,186,205,215,239,216,185,189,187,170,175,166,178,199,232,194,228,155,168,199,188,202,185,187,193,189,179,181,165,192,218,223,231,240,227,234,250,227,225,244,230,234,247,254,243,222,243,221,219,223,245,234,229,187,239,206,214,208,218,222,206,218,219,240,187,199,210,201,192,191,188,193,173,159,181,184,193,185,195,193,191,182,193,214,218,223,219,194,180,225,150,168,159,171,179,177,180,173,177,185,168,189,183,177,181,186,192,193,208,196,190,196,190,197,186,225,215,213,208,204,193,205,175,182,204,187,176,181,152,164,164,157,161,161,175,176,189,200,191,182,194,217,213,175,183,188,177,185,189,192,194,191,187,184,191,208,191,203,201,198,200,189,159,208,187,177,212,199,193,183,202,190,122,145,162,146,137,158,172,191,153,179,172,168,162,180,163,172,165,160,154,178,156,155,174,170,164,174,178,207,213,225,225,245,219,223,220,221,229,231,247,239,221,213,211,203,167,181,162,180,184,192,188,184,180,186,199,187,187,231,186,243,258,250,275,241,245,230,245,249,241,230,243,244,249,238,245,216,230,216,223,215,243,226,213,226,136,124,142,160,190,169,176,186,198,177,185,195,193,201,209,198,205,228,225,222,209,209,218,235,201,211,207,211,212,206,194,191,205,200,201,225,196,187,199,197,193,190,199,186,184,201,208,210,197,181,188,191,192,173,178,190,179,186,210,207,225,225,249,224,245,245,240,234,237,223,235,226,222,228,210,181,214,196,177,175,172,173,178,199,196,199,234,219,213,244,233,230,240,231,231,219,215,222,199,191,186,182,177,180,180,193,199,195,190,181,186,184,185,187,195,177,176,189,167,174,178,183,179,205,190,217,219,243,235,247,248,239,248,216,205,208,207,207,201,213,213,200,232,207,200,223,205,212,234,224,243,246,250,255,236,238,232,240,236,231,215,214,223,183,211,227,231,239,238,237,219,230,222,222,212,219,236,221,224,228,252,248,232,259,224,230,230,190,208,176,204,185,201,229,221,206,198,216,210,202,205,186,192,198,189,181,162,170,160,173,166,173,172,181,147,168,163,156,242,217,200,214,216,203,214,217,207,207,227,207,235,235,215,154,153,166,174,173,187,195,181,186,160,172,181,186,201,197,206,202,192,204,196,225,208,226,235,215,234,204,207,202,171,163,167,174,181,187,176,162,165,169,171,189,189,180,173,193,205,204,213,200,200,181,161,188,194,185,200,195,189,197,202,186,206,191,204,221,221,205,222,177,192,199,213,194,195,202,202,203,220,221,200,197,201,188,194,179,199,201,182,188,204,195,198,204,181,196,200,187,212,210,216,206,213,207,194,195,191,217,216,188,186,203,188,203,211,188,210,199,232,224,223,223,228,222,213,234,204,194,186,170,185,201,178,166,192,190,183,200,225,191,194,202,191,192,180,201,188,185,178,183,196,214,193,187,228,244,219,221,219,242,262,333,330,341,245,231,224,268,303,358,320,362,352,316,318,329,319,321,314,309,381,339,345,344,358,332,336,371,355,355,364,348,334,337,353,368,364,341,354,382,353,342,366,388,345,332,330,362,354,340,353,367,337,382,367,359,326,340,316,329,327,341,331,306,286,164,184,132,181,149,152,123,96,118,149,136,132,121,153,118,180,142,147,162,200,213,193,214,197,159,176,182,201,183,227,213,217,213,150,215,218,212,239,268,257,227,246,259,265,267,274,222,200,178,155,226,224,189,181,174,170,189,194,188,173,120,181,207,197,195,207,169,166,211,197,205,229,222,226,226,175,199,196,191,220,198,191,206,209,207,232,227,218,230,225,213,266,236,268,257,261,241,237,256,231,270,268,285,275,285,253,259,281,261,263,239,235,219,211,208,210,221,220,235,227,215,235,233,247,248,254,267,254,259,275,294,248,252,252,245,207,196,209,212,193,196,199,214,206,218,203,195,209,207,222,228,225,228,220,235,200,267,238,200,213,228,218,211,230,225,235,208,207,214,205,195,191,215,209,245,252,247,238,229,218,220,191,186,200,192,179,199,214,198,221,218,213,202,230,239,244,265,248,245,256,244,253,244,221,229,252,257,235,252,268,261,225,257,256,263,280,272,278,280,294,306,297,278,281,259,239,249,258,249,228,256,241,260,252,239,221,240,245,236,242,213,210,231,209,238,229,236,225,234,214,242,247,238,246,221,226,219,216,223,237,232,237,230,231,213,231,225,210,238,223,237,207,245,216,213,222,234,233,258,243,245,289,376,296,280,364,362,387,338,346,337,296,324,358,353,340,306,297,325,328,303,322,324,314,349,338,327,310,314,317,302,265,29,11,13,12,18,12,12,11,10,11,9,8,7,30]'))
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));

        $this->assertMatchesTextSnapshot($output);
        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT bestAverages FROM ActivityStream')->fetchFirstColumn())
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}