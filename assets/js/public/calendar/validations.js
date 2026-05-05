function validateBase(
    calendarState,
    minimumNumberOfNights,
    alertError
) {
    if (calendarState.diffNights < minimumNumberOfNights) {
        alertError('A minimum of 3 consecutive nights must be selected.');
        return false;
    }
    return true;
}

export function validateRentSelection(
    calendarState,
    minimumNumberOfNights,
    alertError
) {
    if (!validateBase(calendarState, minimumNumberOfNights, alertError)) return false;

    if (!calendarState.isWithinEvent) {
        alertError('Make sure to select dates within your reserved ranges.');
        return false;
    }

    return true;
}

export function validateExchangeSelection(
    calendarState,
    minimumNumberOfNights,
    alertError
) {
    return validateRentSelection(calendarState, minimumNumberOfNights, alertError);
}

export function validateExchangeSelectionPart2(
    calendarState,
    minimumNumberOfNights,
    alertError
) {
    if (!validateBase(calendarState, minimumNumberOfNights, alertError)) return false;

    if (!calendarState.isWithinEventButIsntOwner) {
        alertError('Make sure to select dates within reserved ranges that are not yours.');
        return false;
    }

    if (calendarState.isSelectingTwoEvents) {
        alertError('Make sure you do not select 2 ranges from different owners.');
        return false;
    }

    return true;
}
