import { Button } from '@wordpress/components';

export const MyButton = (props) => {
  const { variant='primary', label='Hello world', disabled=false } = props;

  return (
    <Button
      variant={ variant }
      disabled={ disabled }
    >
      {label}
    </Button>
  );
};
