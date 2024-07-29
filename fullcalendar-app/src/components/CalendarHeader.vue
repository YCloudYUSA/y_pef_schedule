<template>
  <div class="calendar-branch-header">
    <div :class="['calendar-branch-info', { 'hide-title': !isTitleShown }]" v-if="branchTitle">
      <h4>{{ 'Branch name' }}</h4>
      <h2>{{ branchTitle }}</h2>
    </div>
  </div>

  <div class="fullcalendar--header">
    <div class="pdf">
      <button class="download-pdf-button" @click="$emit('downloadPDF')">
        <i class="bi bi-download"></i>
        Download PDF
      </button>
    </div>

    <button class="legend-toggle-button btn btn-dark" data-mdb-ripple-init @click="toggleLegend">Legend +</button>
    <div :class="['checkbox-container', { active: isLegendOpen }]">
      <label
        v-for="category in categories"
        :key="category.name"
        :style="{'border-color': category.color, 'background-color': 'color-mix(in srgb, ' + category.color +', transparent 80%)'}"
      >
        <input
          type="checkbox"
          :value="category.name"
          :checked="isSelected(category.name)"
          @change="emitCategoryChange(category.name, $event.target.checked)"
          class="custom-checkbox-input"
          :style="{'accent-color': category.color}"
        />
        {{ category.name }}
      </label>
    </div>
  </div>
</template>

<script>
import EventService from '../service/EventService';

export default {
  name: 'CalendarHeader',
  props: {
    categories: Array,
    selectedCategories: Array
  },
  data() {
    return {
      isLegendOpen: false,
      isTitleShown: null,
      branchTitle: null,
    };
  },
  mounted() {
    const eventService = new EventService();
    this.branchTitle = eventService.getBranchTitle();
    this.isTitleShown = eventService.isTitleShown();
  },
  methods: {
    toggleLegend() {
      this.isLegendOpen = !this.isLegendOpen;
    },
    isSelected(categoryName) {
      // Make sure selectedCategories is an array before calling includes
      if (Array.isArray(this.selectedCategories)) {
        return this.selectedCategories.includes(categoryName);
      }

      console.error('Error: selectedCategories is expected to be an array but received a different type.', {
        actionNeeded: 'Check the initialization of selectedCategories to ensure it is correctly set as an array. This might involve reviewing the data source or state management logic that provides value to selectedCategories.',
        receivedType: typeof this.selectedCategories,
        receivedValue: this.selectedCategories,
        suggestion: 'If selectedCategories data is dynamically loaded, verify the data structure returned by the server or external data source. Ensure any transformation or assignment operation maintains the array data type.',
      });

      return false;
    },
    emitCategoryChange(categoryName, isChecked) {
      // Create a new array that reflects the change
      let updatedCategories = isChecked
        // Add the category
        ? [...this.selectedCategories, categoryName]
        // Remove the category
        : this.selectedCategories.filter(cat => cat !== categoryName);

      // Make sure there are no duplicates
      updatedCategories = Array.from(new Set(updatedCategories));

      // Emit the updated array of selected categories
      this.$emit('categoryChange', updatedCategories);
    }
  }
};
</script>

<style lang="scss">
.fullcalendar-app {
  .fullcalendar--header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    padding: 30px 0;
  }

  .checkbox-container {
    display: none;
    flex-wrap: wrap;
    margin-top: 20px;
    gap: 3px;
    justify-content: flex-end;

    &.active {
      display: flex;
    }

    label {
      border: 1px solid;
      padding: 0 5px;
      border-radius: var(--wsBorderRadius, 10px);
    }
  }

  .legend-toggle-button {
    display: block;
  }

  .calendar-branch-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;

    .calendar-branch-info {
      text-align: left;

      h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #000;
        text-transform: uppercase;
        letter-spacing: 1px;
      }

      h2 {
        margin: 0;
        font-weight: bold;
        color: #000;
      }
    }
  }

  .hide-title {
    display: none;
  }
}
</style>
