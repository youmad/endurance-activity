<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

enum ActivityImportWarningCode: string
{
    case MissingSessionRecovered = 'missing_session_recovered';
    case MissingLapRecovered = 'missing_lap_recovered';
    case ActivityTimerMismatch = 'activity_timer_mismatch';
    case ActivitySessionCountMismatch = 'activity_session_count_mismatch';
    case TimestampBoundaryAdjusted = 'timestamp_boundary_adjusted';
    case TerminalTimerBoundaryResolutionAdjusted = 'terminal_timer_boundary_resolution_adjusted';
    case LapBoundaryResolutionOverlap = 'lap_boundary_resolution_overlap';
    case SessionBoundaryResolutionOverlap = 'session_boundary_resolution_overlap';
    case SessionLapReferenceMismatch = 'session_lap_reference_mismatch';
    case SessionLengthCountMismatch = 'session_length_count_mismatch';
    case SessionActiveLengthCountMismatch = 'session_active_length_count_mismatch';
    case LapLengthReferenceMismatch = 'lap_length_reference_mismatch';
    case LapActiveLengthCountMismatch = 'lap_active_length_count_mismatch';
    case ActivityDetailBoundaryResolutionOverlap = 'activity_detail_boundary_resolution_overlap';
    case LapTimerDurationExceedsElapsed = 'lap_timer_duration_exceeds_elapsed';
    case SummaryTimerDurationResolutionAdjusted = 'summary_timer_duration_resolution_adjusted';
    case RecordWithoutTimestampSkipped = 'record_without_timestamp_skipped';
    case RecordCoordinatePairSkipped = 'record_coordinate_pair_skipped';
    case DeviceInfoWithoutIndexSkipped = 'device_info_without_index_skipped';
    case PreStartObservationsSkipped = 'pre_start_observations_skipped';
    case UnknownActivityEvent = 'unknown_activity_event';
    case UnknownSessionEvent = 'unknown_session_event';
    case SessionCoordinatePairSkipped = 'session_coordinate_pair_skipped';
    case SegmentCoordinatePairSkipped = 'segment_coordinate_pair_skipped';
    case FailedSegmentEffortOutsideSessionSkipped = 'failed_segment_effort_outside_session_skipped';
    case ActivitySummaryFieldsRecovered = 'activity_summary_fields_recovered';
    case ActivitySummaryTimestampAdjusted = 'activity_summary_timestamp_adjusted';
    case WarningsTruncated = 'warnings_truncated';
}
