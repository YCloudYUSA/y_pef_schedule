<template>
  <div class="calendar-branch-header">
    <div class="calendar-branch-info" v-if="branch">
      <h4>{{ 'Branch name' }}</h4>
      <h2>{{ branch }}</h2>
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
      <label class="custom-checkbox" v-for="category in categories" :key="category.name">
        <input
          type="checkbox"
          :value="category.name"
          :checked="isSelected(category.name)"
          @change="emitCategoryChange(category.name, $event.target.checked)"
          class="custom-checkbox-input"
        />
        <span class="checkmark" :style="{ 'background-color': category.color, 'border-color': category.color }"></span>
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
      branch: null,
    };
  },
  mounted() {
    const eventService = new EventService();
    this.branch = eventService.getBranch();
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
  }

  .custom-checkbox {
    display: flex;
    align-items: center;
    position: relative;
    padding-left: 20px;
    cursor: pointer;
    font-size: 14px;
    user-select: none;
    margin-right: 15px;

    input {
      position: absolute;
      opacity: 0;
      cursor: pointer;
      height: 0;
      width: 0;
    }

    .checkmark {
      position: absolute;
      left: 0;
      height: 17px;
      width: 17px;
      background-color: #eee;
      border-radius: 4px;
      border: 1px solid #ddd;

      &:after {
        content: "";
        position: absolute;
        display: none;
        left: 50%;
        top: 50%;
        width: 5px;
        height: 10px;
        border: solid black;
        border-width: 0 2px 2px 0;
        transform: translate(-50%, -50%) rotate(45deg);
      }
    }

    &:hover input ~ .checkmark {
      background-color: #ccc;
    }

    input:checked ~ .checkmark {
      background-color: #2196F3;
      border: 1px solid #2196F3;
      &:after {
        display: block;
      }
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
}
</style>
