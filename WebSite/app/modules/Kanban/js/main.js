import KanbanModule from "./kanbanModule.js";

const statusTransitions = {
    '💡': { '☑️': 'MovedFromBacklogToSelected', '🔧': 'MovedFromBacklogToInProgress', '🏁': 'MovedFromBacklogToDone' },
    '☑️': { '💡': 'MovedFromSelectedToBacklog', '🔧': 'MovedFromSelectedToInProgress', '🏁': 'MovedFromSelectedToDone' },
    '🔧': { '💡': 'MovedFromInProgressToBacklog', '☑️': 'MovedFromInProgressToSelected', '🏁': 'MovedFromInProgressToDone' },
    '🏁': { '💡': 'MovedFromDoneToBacklog', '☑️': 'MovedFromDoneToSelected', '🔧': 'MovedFromDoneToInProgress' }
};

document.addEventListener("DOMContentLoaded", () => {
    const isOwner = window.IS_OWNER === 'true';
    const module = new KanbanModule(statusTransitions, isOwner);
    module.init();
});
