<template>
  <input
    class="
      form-control
      block
      w-full
      px-3
      py-1.5
      text-base
      font-normal
      text-gray-700
      bg-white bg-clip-padding
      border border-solid border-gray-300
      rounded
      transition
      ease-in-out
      m-0
      focus:text-gray-700
      focus:bg-white
      focus:border-purple-600
      focus:outline-none
    "
    :value="modelValue"
    @input="updateValue"
  />
</template>

<script>
export default {
  props: {
    modelValue: "",
  },
  data() {
    return {};
  },
  methods: {
    updateValue(event) {
      this.$emit("update:modelValue", event.target.value);
    },
  },
};
</script>
