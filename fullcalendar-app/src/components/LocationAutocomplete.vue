<template>
  <div class="autocomplete">
    <input
      type="text"
      class="form-control"
      @input="onInput"
      v-model.trim="search"
      @focus="focused = true"
      @blur="onBlur"
      placeholder="Location"
    />
    <ul v-if="focused && suggestions.length">
      <li
        v-for="suggestion in suggestions"
        :key="suggestion.id"
        @mousedown="selectSuggestion(suggestion)"
      >
        {{ suggestion.name }}
      </li>
    </ul>
  </div>
</template>

<style lang="scss">
.fullcalendar-app {
  .autocomplete {
    ul {
      position: absolute;
      z-index: 1000;
      background-color: white;
      list-style-type: none;
      width: 100%;
      border: 1px solid #ccc;
      margin: 0;
      padding-left: 0;
    }

    li {
      padding: 5px;
      cursor: pointer;

      &:hover {
        background-color: #eee;
      }
    }
  }
}
</style>

<script>
export default {
  name: 'LocationAutocomplete',
  props: {
    value: String,
    fetchSuggestions: Function
  },
  data() {
    return {
      search: '',
      focused: false,
      suggestions: []
    };
  },
  methods: {
    onInput() {
      // Emit the current value to the parent component
      this.$emit('input', this.search);

      if (this.search.length > 2) {
        this.fetchSuggestions(this.search, (suggestions) => {
          // Update the suggestions list
          this.suggestions = suggestions;
        });
      } else {
        // Clear suggestions if the input value is too short
        this.suggestions = [];
      }
    },
    selectSuggestion(suggestion) {
      this.$emit('input', suggestion.name);
      // Emit a separate event for selection
      this.$emit('select', suggestion);
      this.focused = false;
    },
    onBlur() {
      // Use a delay to allow time for the mousedown event to fire on suggestions
      setTimeout(() => {
        this.focused = false;
      }, 200);
    }
  }
};
</script>
